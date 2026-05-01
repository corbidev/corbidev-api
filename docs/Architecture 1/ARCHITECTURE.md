# ARCHITECTURE

## STRUCTURE

src/
- Auth/
  - Login/
    - Domain/
    - Application/
    - Infrastructure/
  - Token/
    - Domain/
    - Application/
    - Infrastructure/
  - Jwt/
    - Infrastructure/
- Api/
  - Logs/
- Shared/
  - ValueObject/
  - Exception/
  - Crypto/
  - Hash/
  - Infrastructure/
  - Kernel/

## RULES

- Feature-based pour le métier uniquement
- Shared uniquement pour le technique
- Aucun code métier dans Shared
- Aucun composant technique global dans une feature

## FLOW

Controller → Application → Domain
                     ↓
              Infrastructure

## CONTRAINTES

- Domain ne dépend jamais de l’infrastructure
- Application orchestre
- Infrastructure ne contient aucun métier
