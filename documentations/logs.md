# Documentation Logs

## Objectif

Le module de logs permet d'enregistrer des événements applicatifs dans l'API via un endpoint HTTP unique.

- Endpoint : `POST /api/logs`
- Contrôleur : `App\RessLogs\Controller\CreateLogController`
- Service métier : `App\RessLogs\Service\LogRecorder`

## Fonctionnement global

- Le contrôleur reçoit un JSON.
- Il récupère la clé source (`sourceApiKey`) depuis le body JSON, l'en-tête `x-api-key` ou un JWT dans l'en-tête `Authorization: Bearer <jwt>`.
- Le service `LogRecorder` valide et enrichit les données.
- Le log est persisté en base (`log_entry`) avec ses relations (`log_level`, `log_env`, `log_source`, `log_url`, `log_uri`, `log_tag`, `log_entry_tag`).

## Endpoint

### Authentification pour obtenir un token JWT (client_credentials)

- Méthode : `POST`
- URL : `/api/logs/token`
- Header requis :
  - `Content-Type: application/json`
- Body JSON requis :
  - `sourceApiKey` (string) — identifiant public de la source
  - `clientSecret` (string) — secret propre au site, stocké haché en base

Note : `clientSecret` est configuré par l'administrateur lors de la création de la source. Il est stocké en base sous forme de hash Argon2id, jamais en clair. Chaque site a son propre secret indépendant.

### Gestion des secrets (commande Symfony)

La commande `app:logs:set-source-secret` crée la source si elle n'existe pas, sinon met à jour son secret.

Exemples :

```bash
php bin/console app:logs:set-source-secret ma-source-api-key --secret="mon-secret-long" --no-interaction
```

```bash
php bin/console app:logs:set-source-secret ma-source-api-key --secret="mon-secret-long" --name="Mon Site" --type="backend" --no-interaction
```

Règles :

- `sourceApiKey` est l'identifiant public de la source.
- `clientSecret` est stocké haché en base (Argon2id).
- si `sourceApiKey` n'existe pas, la source est créée (`name=sourceApiKey`, `type=backend`, active).

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
- Header recommandé :
  - `Content-Type: application/json`
  - `x-api-key: <source_api_key>`
  - ou `Authorization: Bearer <jwt>`

### Authentification JWT (consommateurs)

- Le token JWT est validé (signature + expiration) via la configuration Lexik JWT.
- Le token s'obtient via `POST /api/logs/token` avec une `sourceApiKey` active.
- Si le token est valide, la source est résolue via une claim contenant la clé API.
- Claims supportées (dans cet ordre) :
  - `sourceApiKey`
  - `source_api_key`
  - `apiKey`
  - `api_key`
- Si `x-api-key` est présent et non vide, il reste prioritaire.
- Si le header `Authorization` est présent mais pas au format Bearer, la requête est rejetée en `400`.

### Payload JSON

#### Obligatoire

- `message` (string)
- `url` (string) — URL absolue valide (`http://...` ou `https://...`)
- `sourceId` (int) **ou** `sourceApiKey` (string)

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
- `routeUrl` (string)
- `routeUri` (string)
- `tags` (array d'IDs ou de noms)

## Règles de résolution

- `level` : recherche par id ou nom (`debug`, `info`, `warning`, `error`, `critical`).
- `env` : recherche par id ou nom (`dev`, `test`, `prod`).
- `source` :
  - priorité à `sourceId`, sinon `sourceApiKey` (source active).
  - `sourceApiKey` peut venir du body, de `x-api-key` ou d'un JWT Bearer.
  - erreur si introuvable.
- `url/uri` :
  - `url` est obligatoire et doit etre une URL absolue valide (`http` ou `https`).
  - `uriId` (ou `routeId` pour compatibilite) pointe vers une URI existante.
  - `urlId` pointe vers une URL existante.
  - `uri`/`routeUri` permet de rechercher ou creer une URI.
  - `url`/`routeUrl` permet de rechercher ou creer une URL.
  - une URI peut etre rattachee a une URL.
  - si plus aucune `log_entry` ne reference une URI/URL, elle est supprimee automatiquement.
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
  - champ obligatoire manquant,
  - référence métier introuvable (source/level/env/url/uri/tag id),
  - données invalides.

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
  -H "x-api-key: replace-with-valid-source-api-key" \
  -d '{
    "message": "Erreur sur endpoint /api/users",
    "url": "https://api.corbisier.test/api/users",
    "title": "API Error",
    "level": "error",
    "env": "dev",
    "httpStatus": 500,
    "durationMs": 231,
    "routeUrl": "/api/users",
    "routeUri": "/api/users",
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

## Exemple de génération de token JWT

```bash
curl -X POST "http://127.0.0.1:8000/api/logs/token" \
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
- Le schéma SQL de référence est dans :
  - `database/sql/log.sql`
