# Documentation Logs

## Objectif

Le module de logs permet d'enregistrer des événements applicatifs dans l'API via un endpoint HTTP unique.

- Endpoint : `POST /api/logs`
- Contrôleur : `App\RessLogs\Controller\CreateLogController`
- Service métier : `App\RessLogs\Service\LogRecorder`
- Auth JWT mutualisée : voir `documentations/auth.md`

## Fonctionnement global

- Le contrôleur reçoit un JSON.
- Il exige un JWT valide dans l'en-tête `Authorization: Bearer <jwt>` et résout la source uniquement depuis ce token.
- Le service `LogRecorder` valide et enrichit les données.
- Le log est persisté en base (`${SQL_PREFIXE}log_entry`) avec ses relations (`${SQL_PREFIXE}log_level`, `${SQL_PREFIXE}log_env`, `${SQL_PREFIXE}auth_credential`, `${SQL_PREFIXE}log_url`, `${SQL_PREFIXE}log_uri`, `${SQL_PREFIXE}log_tag`, `${SQL_PREFIXE}log_entry_tag`).

## Endpoint

### Authentification pour obtenir un token JWT (client_credentials)

- Le point d'entree principal est `POST /api/auth/token`.
- L'ancienne URL `POST /api/logs/token` reste acceptee pour compatibilite.
- Le detail de cette gestion est documente dans `documentations/auth.md`.

Note : `clientSecret` est configuré par l'administrateur lors de la création de la source. Il est stocké en base dans la table `${SQL_PREFIXE}auth_credential` sous forme de hash Argon2id, jamais en clair. Cette meme table porte aussi le nom, le type et `api_key` de la source.

### Gestion des secrets (commande Symfony)

La commande `app:logs:set-source-secret` est maintenant implementée dans `App\RessAuth\Command\SetLogSourceSecretCommand`. Elle cree la source canonique si elle n'existe pas, sinon met a jour son secret.

Exemples :

```bash
php bin/console app:logs:set-source-secret ma-source-api-key --secret="mon-secret-long" --no-interaction
```

```bash
php bin/console app:logs:set-source-secret ma-source-api-key --secret="mon-secret-long" --name="Mon Site" --type="backend" --no-interaction
```

Règles :

- `sourceApiKey` est l'identifiant public de la source.
- `clientSecret` est stocké haché en base (Argon2id) dans `${SQL_PREFIXE}auth_credential`.
- si `sourceApiKey` n'existe pas, la source canonique est créée dans `${SQL_PREFIXE}auth_credential` (`name=sourceApiKey`, `type=backend`, active).

Réponse succès (`201 Created`) :

```json
{
  "token_type": "Bearer",
  "access_token": "<jwt>",
  "expires_in": 3600
}
```

### Requête

- Méthode : `POST`
- URL : `/api/logs`
- Header obligatoire :
  - `Content-Type: application/json`
  - `Authorization: Bearer <jwt>`

### Authentification JWT (consommateurs)

- Le token JWT est validé (signature + expiration) via la configuration Lexik JWT.
- Le token s'obtient via `POST /api/auth/token` avec une `sourceApiKey` active.
- Si le token est valide, la source est résolue via une claim contenant la clé API.
- Claims supportées (dans cet ordre) :
  - `sourceApiKey`
  - `source_api_key`
  - `apiKey`
  - `api_key`
- Si le header `Authorization` est absent, la requête est rejetée en `400`.
- Si le header `Authorization` est présent mais pas au format Bearer, la requête est rejetée en `400`.
- Si le Bearer JWT est invalide ou expiré, la requête est rejetée en `403`.
- Les champs `sourceApiKey` et `sourceId` sont interdits dans le body de `POST /api/logs` : la source est deja deduite du Bearer JWT. S'ils sont envoyes, la requete est rejetee en `400`.

### Payload JSON

#### Obligatoire

- `message` (string)
- `url` (string) — URL absolue valide (`http://...` ou `https://...`)

#### Optionnel

- `title` (string|null)
- `httpStatus` (int|null)
- `durationMs` (int|null)
- `fingerprint` (string|null)
- `context` (object|null)
- `ts` (string datetime ISO8601 ou `DateTimeImmutable`)
- `createdAt` (string datetime ISO8601 ou `DateTimeImmutable`)
- `level` (int|string) — défaut: `200` (`info`)
- `env` (int|string) — défaut: `1` (`dev`)
- `urlId` (int)
- `uriId` (int)
- `routeId` (int)
- `uri` (string)
- `tags` (array d'IDs ou de noms)

## Règles de résolution

- `level` : recherche par id ou nom (`debug`, `info`, `warning`, `error`, `critical`).
- `env` : recherche par id ou nom (`dev`, `test`, `prod`).
- `source` :
  - la source canonique active est résolue uniquement depuis les claims du JWT Bearer.
  - erreur si introuvable.
- `url/uri` :
  - `url` est obligatoire et doit etre une URL absolue valide (`http` ou `https`).
  - `uriId` (ou `routeId` pour compatibilite) pointe vers une URI existante.
  - `urlId` pointe vers une URL existante.
  - `uri` permet de rechercher ou creer une URI.
  - `url` permet de rechercher ou creer une URL.
  - si `url` contient deja un path, ce path est extrait automatiquement pour alimenter `uri`.
  - si `url` contient un path et que `uri` est aussi fourni avec une valeur differente, la requete est rejetee.
  - une URI peut etre rattachee a une URL.
  - si plus aucune `${SQL_PREFIXE}log_entry` ne reference une URI/URL, elle est supprimee automatiquement.
- `tags` :
  - accepte id ou nom,
  - crée les tags manquants par nom,
  - ignore les doublons dans la requête.

## Réponses API

### Succès

- Code : `201 Created`
- Body :

```json
{
  "id": 123,
  "status": "created"
}
```

### Erreurs

- `400 Bad Request` si :
  - JSON invalide,
  - body non objet JSON,
  - header `Authorization` absent,
  - header `Authorization` present mais mal forme,
  - `sourceApiKey` ou `sourceId` présents dans le body,
  - champ obligatoire manquant,
  - référence métier introuvable (source/level/env/url/uri/tag id),
  - données invalides.

- `403 Forbidden` si :
  - Bearer JWT invalide ou expire.

Exemple :

```json
{
  "error": "Le champ \"message\" est obligatoire."
}
```

## Exemple de requête

```bash
curl -X POST "http://127.0.0.1:8000/api/logs" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <jwt_avec_claim_sourceApiKey>" \
  -d '{
    "message": "Erreur sur endpoint /auth/users",
    "url": "https://corbisier.test",
    "uri": "/auth/users",
    "title": "API Error",
    "level": "error",
    "env": "dev",
    "httpStatus": 500,
    "durationMs": 231,
    "context": {
      "method": "GET",
      "traceId": "trace-12345",
      "userId": 42
    },
    "tags": ["backend", "exception"]
  }'
```

## Exemple de requête avec JWT

```bash
curl -X POST "http://127.0.0.1:8000/api/logs" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <jwt_avec_claim_sourceApiKey>" \
  -d '{
    "message": "Erreur sur endpoint /api/users",
    "url": "https://api.corbisier.test/api/users",
    "level": "error",
    "env": "prod"
  }'
```

Dans cet exemple, l'API normalise automatiquement la requete en conservant `https://api.corbisier.test` comme URL et `/api/users` comme URI.

## Exemple de génération de token JWT

```bash
curl -X POST "http://127.0.0.1:8000/api/auth/token" \
  -H "Content-Type: application/json" \
  -d '{
    "sourceApiKey": "identifiant-de-votre-site",
    "clientSecret": "votre-secret-par-site"
  }'
```

## Postman

Une collection prête à l'import est disponible :

- `api/postman/corbidev-api-logs.postman_collection.json`
- Elle inclut le dossier `Scenario - JWT token then log` (génération du token puis envoi du log avec Bearer).

## Notes d'implémentation

- Le flush Doctrine est réalisé à chaque appel de `record()`.
- `ts` et `createdAt` sont initialisés à `now` si absents.
- `source_id` dans `${SQL_PREFIXE}log_entry` pointe directement vers `${SQL_PREFIXE}auth_credential`.
- Le schéma SQL de référence est dans :
  - `database/sql/log.sql`
