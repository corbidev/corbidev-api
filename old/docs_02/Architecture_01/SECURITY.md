# SECURITY

## JWT

- TTL court (5 à 15 min)
- signé
- validé à chaque requête

### VALIDATION SERVEUR

- utilisateur actif
- tokenVersion obligatoire

jwt.tv != user.tokenVersion → rejet

## AUTH USER

- Access Token court
- Refresh Token long

### Refresh Token

- hash uniquement
- usage unique
- rotation obligatoire

## TOKEN SINGLE USE

Structure :
- token_hash
- expires_at
- used_at

### CONSOMMATION

UPDATE auth_token
SET used_at = NOW()
WHERE token_hash = :hash
  AND used_at IS NULL
  AND expires_at > NOW()

Résultat :
- 1 → OK
- 0 → rejet

## SERVER TO SERVER

- API Key recommandé
- ou JWT long

## CSRF

- Header → pas nécessaire
- Cookie → obligatoire
