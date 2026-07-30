Je suivrais donc cet ordre :

1. ApiSecretHasher
2. ApiTimestampValidator
3. ApiNonceValidator
4. ApiSignatureValidator
5. ApiClientScopeChecker
6. ApiClientAuthenticator
7. ApiRequestLogger
8. ApiRequestValidator

Pourquoi cet ordre ?

1. ApiSecretHasher : aucune dépendance, pure logique.
2. ApiTimestampValidator : aucune dépendance, pure logique.
3. ApiNonceValidator : dépend seulement du repository des logs/requêtes.
4. ApiSignatureValidator : dépend du hasher.
5. ApiClientScopeChecker : dépend de l'entité ApiClient et de ses scopes.
6. ApiClientAuthenticator : s'appuie sur les repositories et les validateurs précédents.
7. ApiRequestLogger : dépend du repository ApiRequestLog.
8. ApiRequestValidator : n'est plus qu'un orchestrateur qui compose les services précédents.

À chaque étape :

1. écrire les tests unitaires ;
2. implémenter la classe ;
3. refactoriser si nécessaire.

Ainsi, lorsque tu arrives à ApiRequestValidator, il n'y a plus de logique métier à inventer : il ne fait qu'enchaîner des services déjà testés individuellement.
C'est une bonne adéquation avec les principes que tu as définis pour ce projet.
