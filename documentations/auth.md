# Documentation Auth

## Objectif

Le module auth centralise l'emission et la lecture des JWT utilises par les autres ressources de l'API.

- Endpoint principal : POST /api/auth/token
- Controleur : App\RessAuth\Controller\CreateAccessTokenController
- Services : App\RessAuth\Security\AccessTokenIssuer et App\RessAuth\Security\AccessTokenResolver
- Commande de gestion des secrets : App\RessAuth\Command\SetLogSourceSecretCommand
- Table canonique : `${SQL_PREFIXE}auth_credential`

## Emission d'un token JWT

- Methode : POST
- URL : /api/auth/token
- URL legacy conservee : /api/logs/token
- Header requis : Content-Type: application/json
- Body JSON requis :
  - sourceApiKey (string)
  - clientSecret (string)

Reponse succes :

```json
{
  "token_type": "Bearer",
  "access_token": "<jwt>",
  "expires_in": 3600
}
```

## Validation et resolution

- Le Bearer JWT est valide via Lexik JWT.
- Les claims source supportees sont : sourceApiKey, source_api_key, apiKey, api_key.
- L'identifiant source supporte les claims : sourceId, source_id.
- Les ressources comme logs reutilisent ce module au lieu d'embarquer leur propre logique JWT.
- Toute verification de token doit passer par App\RessAuth\Security\AccessTokenResolverInterface.

## Stockage des credentials

- La table canonique `${SQL_PREFIXE}auth_credential` stocke aussi le nom, le type et `api_key` de la source emettrice.
- Le secret client y est stocke hache, jamais en clair.
- Les tables SQL de l'application sont prefixees via la variable `SQL_PREFIXE` definie dans `.env`.

## Gestion des secrets

- La commande `app:logs:set-source-secret` est portee par `App\RessAuth\Command\SetLogSourceSecretCommand`.
- Elle cree la source canonique si elle n'existe pas encore, puis enregistre ou remplace le hash Argon2id du secret.
- Le nom de commande reste `app:logs:set-source-secret` pour compatibilite avec les usages existants.
