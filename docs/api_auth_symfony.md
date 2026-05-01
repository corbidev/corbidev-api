# PROJECT: API AUTH SYMFONY

## CONTEXTE

- PHP 8.4  
- Symfony 8  
- Hébergement : mutualisé (IONOS)  
- Objectif : API d’authentification sécurisée (tokens + JWT)  

### Contraintes

- simplicité  
- pas d’asynchrone  
- contrôle total  

---

## GLOBAL_RULES

- TOUJOURS écrire les tests AVANT l’implémentation  
- SI ce n’est pas testé → code invalide  
- Garder une architecture simple  
- PAS de sur-ingénierie  
- PAS de logique cachée dans le code applicatif  
- Nommage explicite uniquement  

### PHPDoc

- PHPDoc obligatoire en français pour chaque classe, méthode et propriété  
- Le PHPDoc doit expliquer l’intention (pourquoi), pas seulement le quoi  
- Interdiction de PHPDoc vide ou inutile  

---

## FORBIDDEN

- API Platform  
- Messenger / Async  
- CQRS complexe  
- Event Bus  
- React / Vue  
- shadcn  
- dossiers globaux Utils / Helper  

---

## STACK_BACKEND

- Symfony 8  
- Monolog  
- Symfony HttpClient  
- JWT (avec encapsulation et contrôle interne)  

---

## STACK_FRONTEND

### Par défaut
- Twig  
- JavaScript natif  

### Optionnel
- Vite  
- Tailwind (uniquement dans des composants)  
- PostCSS  
- Autoprefixer  

---

## ARCHITECTURE

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

---

## ARCHITECTURE_RULES

- Structure basée sur les fonctionnalités pour tout le code métier  
- Un dossier Shared est autorisé UNIQUEMENT pour le technique transversal  
- Pas de dossiers globaux Service/Utils  

### Séparation stricte

- Domain = logique métier (uniquement dans les features)  
- Application = orchestration  
- Infrastructure = technique  
- UI = contrôleurs  

### Règles

- Aucun code métier dans Shared  
- Aucun composant technique global dans une feature s’il est réutilisable  

---

## FLOW_RULE

Controller → Application → Domain
                     ↓
              Infrastructure

---

## SHARED_RULES

Shared = uniquement technique  

### Autorisé

- ValueObject techniques (Email, Uuid…)  
- Exceptions techniques  
- Crypto  
- Hash  
- Logging  
- Kernel  
- Infrastructure technique  

### Interdit

- Toute logique métier  

---

## TESTING_STRATEGY

Tests d’abord, code ensuite  

---

## SECURITY_RULES

### JWT

- TTL court (5 à 15 minutes)  
- Validation serveur obligatoire  
- Vérification tokenVersion  

### Tokens

- usage unique  
- stockage hash  
- consommation atomique  

---

## LOGGING_RULES

- logs structurés  
- request_id obligatoire  
- pas de données sensibles  

---

## GOLDEN_RULE

SI CE N’EST PAS TESTÉ → ÇA N’EXISTE PAS
