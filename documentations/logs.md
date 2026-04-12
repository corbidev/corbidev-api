# Documentation Logs

## Objectif

Le module de logs permet d'enregistrer des événements applicatifs dans l'API via un endpoint HTTP unique.

- Endpoint : `POST /api/logs`
- Contrôleur : `App\Controller\Log\CreateLogController`
- Service métier : `App\Service\LogRecorder`

## Fonctionnement global

1. Le contrôleur reçoit un JSON.
2. Il récupère la clé source (`sourceApiKey`) depuis :
   - le body JSON, ou
   - l'en-tête `x-api-key`, ou
   - l'en-tête `Authorization: Bearer <token>`.
3. Le service `LogRecorder` valide et enrichit les données.
4. Le log est persisté en base (`log_entry`) avec ses relations (`log_level`, `log_env`, `log_source`, `log_route`, `log_tag`, `log_entry_tag`).

## Endpoint

### Requête

- Méthode : `POST`
- URL : `/api/logs`
- Header recommandé :
  - `Content-Type: application/json`
  - `x-api-key: <source_api_key>`

### Payload JSON

#### Obligatoire

- `message` (string)
- `sourceId` (int) **ou** `sourceApiKey` (string)

#### Optionnel

- `title` (string|null)
- `url` (string|null) — auto-rempli avec l'URL de la requête si absent
- `httpStatus` (int|null)
- `durationMs` (int|null)
- `fingerprint` (string|null)
- `context` (object|null)
- `ts` (string datetime ISO8601 ou `DateTimeImmutable`)
- `createdAt` (string datetime ISO8601 ou `DateTimeImmutable`)
- `level` (int|string) — défaut: `200` (`info`)
- `env` (int|string) — défaut: `1` (`dev`)
- `routeId` (int)
- `routeUrl` (string)
- `routeUri` (string)
- `tags` (array d'IDs ou de noms)

## Règles de résolution

- `level` : recherche par id ou nom (`debug`, `info`, `warning`, `error`, `critical`).
- `env` : recherche par id ou nom (`dev`, `test`, `prod`).
- `source` :
  - priorité à `sourceId`, sinon `sourceApiKey` (source active).
  - erreur si introuvable.
- `route` :
  - si `routeId` fourni, doit exister.
  - `routeUri` sert a resoudre la route (recherche puis creation si inexistante).
  - si `routeUri` est absent et `routeId` absent, aucune route n'est liee.
  - `routeUrl` est informatif et independant de `routeUri`.
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
  - référence métier introuvable (source/level/env/route/tag id),
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

## Postman

Une collection prête à l'import est disponible :

- `api/postman/corbidev-api-logs.postman_collection.json`

## Notes d'implémentation

- Le flush Doctrine est réalisé à chaque appel de `record()`.
- `ts` et `createdAt` sont initialisés à `now` si absents.
- Le schéma SQL de référence est dans :
  - `database/sql/log.sql`
