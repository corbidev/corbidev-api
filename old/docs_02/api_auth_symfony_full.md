# PROJECT: API AUTH SYMFONY (VERSION COMPLÈTE)

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

# GLOBAL_RULES

- TOUJOURS écrire les tests AVANT l’implémentation  
- SI ce n’est pas testé → code invalide  
- Garder une architecture simple  
- PAS de sur-ingénierie  
- PAS de logique cachée dans le code applicatif  
- Nommage explicite uniquement  

## PHPDoc

- PHPDoc obligatoire en français pour chaque classe, méthode et propriété  
- Le PHPDoc doit expliquer l’intention (pourquoi), pas seulement le quoi  
- Interdiction de PHPDoc vide ou inutile  

---

# FORBIDDEN

- API Platform  
- Messenger / Async  
- CQRS complexe  
- Event Bus  
- React / Vue  
- shadcn  
- dossiers globaux Utils / Helper  

---

# STACK_BACKEND

- Symfony 8  
- Monolog  
- Symfony HttpClient  
- JWT avec encapsulation interne  

---

# STACK_FRONTEND

## Par défaut
- Twig  
- JavaScript natif  

## Optionnel
- Vite  
- Tailwind (composants uniquement)  
- PostCSS  
- Autoprefixer  

---

# ARCHITECTURE

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

# ARCHITECTURE_RULES

- Feature-based pour tout le métier  
- Shared uniquement pour le technique  
- Pas de Service/Utils globaux  

## Séparation

- Domain = logique métier pure  
- Application = orchestration  
- Infrastructure = technique  
- UI = contrôleurs  

## Règles

- Aucun métier dans Shared  
- Aucun composant global dans une feature si réutilisable  

---

# FLOW_RULE

Controller → Application → Domain
                     ↓
              Infrastructure

## Contraintes

- Domain indépendant  
- Application orchestre  
- Infrastructure sans métier  

---

# SHARED_RULES

Shared = technique uniquement  

## Autorisé

- ValueObject génériques  
- Exceptions  
- Crypto  
- Hash  
- Logging  
- Filesystem  
- Kernel  

## Interdit

- Toute logique métier  

---

# TESTING_STRATEGY

Tests FIRST, code AFTER  

---

# TDD_CYCLE

1. Write test  
2. Fail  
3. Minimal code  
4. Pass  
5. Refactor  

---

# TEST_TYPES

## Domain (priorité)

- PHP pur  
- sans framework  
- sans DB  

## Application

- orchestration  

## Infrastructure

- JWT  
- repository  

---

# SECURITY_RULES

## JWT

- signé  
- validé à chaque requête  
- TTL court (5–15 min)  

### Validation serveur obligatoire

- utilisateur actif  
- tokenVersion vérifiée  

### Structure

JWT contient :
- sub
- tv (tokenVersion)

### Règle

jwt.tv ≠ user.tokenVersion → rejet  

---

## AUTH UTILISATEUR

- Access Token court  
- Refresh Token long  

### Refresh Token

- stocké hash  
- single use  
- rotation obligatoire  

### À chaque refresh

- invalider ancien  
- générer nouveau  

---

## SERVEUR À SERVEUR

- API Key (recommandé)  
OU  
- JWT long  

Avec :

- validation serveur  
- révocation possible  

---

## TOKEN SINGLE USE

### Structure

- token_hash  
- expires_at  
- used_at  

### Consommation

UPDATE atomique obligatoire :

UPDATE auth_token
SET used_at = NOW()
WHERE token_hash = :hash
  AND used_at IS NULL
  AND expires_at > NOW()

### Résultat

- 1 ligne → OK  
- 0 → rejet  

---

## CSRF

- Authorization header → pas nécessaire  
- Cookie → CSRF obligatoire  

### Recommandation

Utiliser Authorization header  

---

# INFRASTRUCTURE

## Type 1 — Générique

- filesystem  
- logging  
- http  

## Type 2 — Critique

- JWT  
- tokens  
- auth  

### Obligations

- testée  
- encapsulée  
- maîtrisée  

---

# NO_HIDDEN_LOGIC

- logique explicite dans le code  
- pas de logique métier en config  

## Obligations

- wrapper des bundles  
- controllers explicites  

---

# LOGGING_RULES

## Obligatoire

- logs structurés  
- niveaux  
- request_id  

## Contexte minimal

- request_id  
- message  
- level  

## Optionnel

- user_id  
- route  
- ip  

## Interdit

- password  
- token  
- JWT  

---

# DEVELOPMENT_WORKFLOW

1. Test Domain  
2. Test Application  
3. Implémentation Domain  
4. Implémentation Application  
5. Implémentation Infrastructure  
6. Validation  

---

# PHILOSOPHIE

- simplicité  
- sécurité  
- lisibilité  
- pas de magie non maîtrisée  

---

# GOLDEN_RULE

SI CE N’EST PAS TESTÉ → ÇA N’EXISTE PAS
