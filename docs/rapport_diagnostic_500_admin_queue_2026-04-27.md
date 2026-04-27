# Rapport de diagnostic - erreurs 500, routes admin et pipeline logs queue/Bdd

Date: 2026-04-27
Périmètre audité: configuration Symfony (`config/*`), environnement (`api/.env*`), code applicatif (`api/src/*`), état runtime (commandes `bin/console`), logs fournis (`api/var/log/error.log`).

## 1) Résumé exécutif

Le problème principal est une rupture de configuration d'environnement côté sécurité: les variables `API_HOST_REGEX` et `AUTH_HOST_REGEX` sont référencées dans les firewalls mais absentes des variables réellement chargées.

Conséquences observées:
- erreurs 500 sur requêtes HTTP (dont `/admin/login`) au moment de la résolution du firewall;
- la route admin existe bien, mais la requête échoue avant l'exécution du contrôleur;
- le pipeline de logs vers queue/Bdd est partiellement configuré mais ne reçoit pas les logs d'exception "app" (mauvais canal Monolog), ce qui explique l'absence de queue dans `var/log_queue`.

En plus de la cause principale, deux défauts aggravants ont été trouvés:
- les 404 (ex: `/favicon.ico`) peuvent être reclassées en 500 par `ExceptionMapper`;
- la commande retry (`app:process-log-retry`) est inutilisable sans `MAILER_DSN`/`MAIL_FROM`/`MAIL_TO`.

## 2) Symptômes confirmés

### 2.1 Erreur 500 liée à variable d'environnement manquante

Preuve log (`api/var/log/error.log`):
- `EnvNotFoundException: Environment variable not found: "API_HOST_REGEX"`
- stack trace passant par le matcher security firewall.

Preuve config:
- `api/config/packages/security.yaml`
  - firewall `api`: `host: '%env(API_HOST_REGEX)%'`
  - firewall `auth`: `host: '%env(AUTH_HOST_REGEX)%'`

Preuve runtime:
- `php bin/console debug:container --env-vars`
  - `API_HOST_REGEX`: `n/a`
  - `AUTH_HOST_REGEX`: `n/a`
  - warning Symfony: variables manquantes.

Conclusion:
- requête HTTP -> construction/évaluation firewall -> env manquante -> 500.

### 2.2 Route admin/login existante mais indisponible en contexte de panne

Preuve routing:
- `php bin/console router:match /admin/login --method=GET --host=api.corbisier.test`
  - route `admin_login` trouvée (contrôleur `AdminAuthController::login`).

Conclusion:
- le problème n'est pas une absence de route, mais un échec en amont (security/env) pendant le cycle requête.

### 2.3 404 potentiellement journalisées en 500

Preuve log:
- `NotFoundHttpException` pour `/favicon.ico` avec `httpStatus: 500` dans le contexte log.

Preuve code:
- `api/src/Shared/Infrastructure/Exception/ExceptionMapper.php`
  - `mapHttpException(404)` retourne `ApiError(RESOURCE_NOT_FOUND, ..., businessCode=UNKNOWN_ERROR)` via `businessError(...)`.
  - `resolveHttpStatus(...)` priorise le `businessCode` et lit `GEN_999` => 500 depuis `business_errors.yaml`.

Conclusion:
- des 404 peuvent remonter en 500 selon ce mapping, brouillant le diagnostic et les métriques.

## 3) Diagnostic pipeline logs -> queue -> Bdd

## 3.1 Entrée API logs opérationnelle côté routing

Preuve:
- `CreateLogEventCollectionDto` expose `POST /api/logs` via API Platform (host `%api_host%`).
- `router:match /api/logs --method=POST --host=api.corbisier.test` -> route `_api_/logs_post` OK.

## 3.2 Queue fichier inexistante à l'instant T

État disque:
- `api/var/` contient `cache/` et `log/` uniquement.
- dossiers absents: `api/var/log_queue`, `api/var/log_queue_errors`.

Interprétation:
- aucun batch n'a été enfilé récemment OU l'ingestion `/api/logs` n'est pas alimentée.

## 3.3 Cause probable de "pas de queue": mauvais canal Monolog pour les exceptions

Configuration:
- `api/config/packages/monolog.yaml`
  - handlers queue API attachés aux channels `technical` et `business`.

Injection réelle du listener d'exception:
- `php bin/console debug:container App\Shared\Infrastructure\Exception\ApiExceptionListener`
  - argument logger injecté: `Service(monolog.logger)` (channel `app`).
- `php bin/console debug:container monolog.logger`
  - argument = `app`.

Conclusion:
- les exceptions applicatives partent sur le channel `app`.
- les handlers queue `api_logs_technical/business` ne captent pas `app`.
- résultat: erreurs visibles en `error.log`, mais pas de batch vers `/api/logs` -> pas de fichiers queue -> pas de persistance Bdd.

## 3.4 Commandes de consommation queue

Commande principale:
- `php bin/console app:process-log-queue` s'exécute (retour actuel: `No files`).

Commande retry:
- `php bin/console app:process-log-retry --help` échoue:
  - `Environment variable not found: "MAILER_DSN"`.

Conclusion:
- le retry est cassé si variables mail absentes.
- la consommation principale fonctionne, mais inutile tant qu'il n'y a pas de fichiers queue.

## 4) Points de cohérence/conf supplémentaires

- `api/.env` ne contient pas `API_HOST_REGEX` ni `AUTH_HOST_REGEX`, alors que les exemples racine (`env*.symfony.example`) les déclarent.
- `api/.env` signale aussi plusieurs variables manquantes au runtime (`CRON_TOKEN`, `MAILER_DSN`, `MAIL_FROM`, `MAIL_TO`).
- `api/config/routes.yaml` déclare bien les contrôleurs admin (`api_admin` -> `src/Api/Jwt/Controller`).
- `ApiConsumerController` applique une garde session (`checkAccess`) pour `/admin/consumers*`.

## 5) Causes racines (priorisées)

P0 - Bloquant prod/dev HTTP
1. Variables `API_HOST_REGEX`/`AUTH_HOST_REGEX` manquantes alors qu'exigées par `security.yaml`.

P1 - Observabilité / faux positifs 500
2. `ExceptionMapper` associe certains `HttpException` (404) à `BusinessErrorCode::UNKNOWN_ERROR`, ce qui réécrit le status en 500 via registry.

P1 - Pipeline logs vers queue/Bdd
3. `ApiExceptionListener` log sur channel `app`, non couvert par les handlers queue dédiés (`technical`, `business`).

P2 - Résilience retry
4. `app:process-log-retry` dépend de variables mail non définies (`MAILER_DSN`, `MAIL_FROM`, `MAIL_TO`).

## 6) Plan de correction recommandé

Étape 1 - Corriger l'environnement (immédiat)
1. Ajouter dans `api/.env` (ou `.env.local`):
   - `API_HOST_REGEX=^api\.corbisier\.test$`
   - `AUTH_HOST_REGEX=^auth\.corbisier\.test$`
2. Ajouter les variables manquantes minimales:
   - `MAILER_DSN=...`
   - `MAIL_FROM=...`
   - `MAIL_TO=...`
   - `CRON_TOKEN=...`

Étape 2 - Corriger le mapping d'erreurs HTTP
1. Dans `ExceptionMapper::mapHttpException`, éviter d'associer 404 à `UNKNOWN_ERROR`.
2. Retourner un `ApiError` sans business code générique pour 404, ou un business code dédié 404 avec `http_status: 404`.

Étape 3 - Rétablir la chaîne logs -> queue
1. Soit injecter un logger de channel `technical`/`business` dans `ApiExceptionListener`.
2. Soit ajouter un handler queue couvrant le channel `app`.
3. Vérifier ensuite la création de `api/var/log_queue/queue_*.log` après une erreur de test.

Étape 4 - Vérification des commandes et cron
1. Tester:
   - `php bin/console app:process-log-queue`
   - `php bin/console app:process-log-retry --help`
2. Vérifier la crontab réelle utilise bien `app:process-log-queue` / `app:process-log-retry`.
3. Harmoniser la documentation (certains fichiers mentionnent encore `app:logs:consume` / `app:logs:retry`).

## 7) Checklist de validation post-correctifs

1. `php bin/console debug:container --env-vars` ne remonte plus `API_HOST_REGEX`/`AUTH_HOST_REGEX` en manquantes.
2. `GET https://api.corbisier.test/admin/login` répond sans 500.
3. Une erreur applicative test génère un fichier `api/var/log_queue/queue_*.log`.
4. `app:process-log-queue` insère des lignes en Bdd (`log_event` ou table cible selon mapping).
5. Un 404 (`/favicon.ico`) reste en 404 dans réponse et logs, pas 500.
6. `app:process-log-retry` démarre sans exception d'env mail.

## 8) Conclusion

Le point de casse principal est clairement configurationnel (env regex manquantes) et explique vos 500 / routes admin "HS".

L'absence de logs queue/Bdd est ensuite cohérente avec la configuration actuelle des channels Monolog (exceptions en `app`, queue branchée sur `technical`/`business`).

Enfin, le mapping 404->500 et le retry cassé par variables mail manquantes dégradent la fiabilité opérationnelle.

Le système est récupérable rapidement avec un correctif d'env + alignement des channels logger + ajustement du mapper d'exception.

## 9) Correctifs appliqués (2026-04-27)

Les correctifs suivants ont été implémentés dans le code:

1. Variables d'environnement ajoutées dans `api/.env`:
  - `CRON_TOKEN`
  - `MAIL_FROM`
  - `MAIL_TO`
  - `MAILER_DSN=null://null`

2. Injection logger du listener d'exception réalignée:
  - `api/config/services.yaml`
  - `App\Shared\Infrastructure\Exception\ApiExceptionListener` utilise désormais `@monolog.logger.technical`.

3. Mapping HTTP 404 corrigé:
  - `api/src/Shared/Infrastructure/Exception/ExceptionMapper.php`
  - `404` retourne maintenant `new ApiError(ErrorCode::RESOURCE_NOT_FOUND, 'Resource not found')` (sans business code `UNKNOWN_ERROR`).

## 10) Validations exécutées après correctifs

1. `php bin/console debug:container --env-vars`
  - `API_HOST_REGEX` et `AUTH_HOST_REGEX` résolues
  - `CRON_TOKEN`, `MAILER_DSN`, `MAIL_FROM`, `MAIL_TO` résolues

2. `php bin/console debug:container App\Shared\Infrastructure\Exception\ApiExceptionListener`
  - logger injecté confirmé: `Service(monolog.logger.technical)`

3. `php bin/console app:process-log-retry --help`
  - commande accessible (plus d'exception `MAILER_DSN` manquante)

4. `php bin/console router:match /admin/login --method=GET --host=api.corbisier.test`
  - route `admin_login` toujours résolue

5. `php bin/console app:process-log-queue`
  - commande opérationnelle (`No files` dans l'état courant, ce qui est cohérent sans batch entrant)
