# Architecture `src/Auth`

## Philosophie

Le dossier `Auth` regroupe exclusivement les fonctionnalités liées à l'authentification et à l'autorisation.

L'objectif est de fournir un point central de gestion des identités pour l'ensemble des APIs de la plateforme.

Exemples :

- connexion utilisateur
- renouvellement de session
- authentification serveur à serveur
- contrôle des accès aux sites
- gestion des JWT

---

# Règle de profondeur

Afin de conserver une architecture simple et facilement navigable :

- `src` est considéré comme le niveau racine
- profondeur maximale recommandée : 3 niveaux
- aucun dossier inutile
- aucun dossier technique générique

Exemple autorisé :

```text
src/
└── Auth/
    └── Login/
        └── Application/
```

Exemple interdit :

```text
src/
└── Auth/
    └── Login/
        └── Application/
            └── Service/
                └── Internal/
```

---

# Structure

```text
src/
└── Auth/
    ├── Login/
    ├── RefreshToken/
    ├── ClientCredentials/
    ├── SiteAccess/
    └── Jwt/
```

---

# Login

```text
Auth/
└── Login/
```

## Responsabilité

Authentifier un utilisateur.

## Exemple

```http
POST /api/v1/auth/login
```

## Contenu

### Domain

Règles métier liées à la connexion.

Exemples :

- UserAuthenticatorInterface
- AuthenticatedUser

### Application

Cas d'usage de connexion.

Exemple :

- LoginUserUseCase

### Infrastructure

Accès aux données utilisateurs.

Exemples :

- DoctrineUserRepository
- PasswordHasher

### UI

Contrôleurs et DTO HTTP.

Exemples :

- LoginController
- LoginRequest
- LoginResponse

---

# RefreshToken

```text
Auth/
└── RefreshToken/
```

## Responsabilité

Renouveler un JWT expiré à partir d'un refresh token valide.

## Exemple

```http
POST /api/v1/auth/refresh
```

## Contenu

### Domain

Règles métier des refresh tokens.

### Application

Cas d'usage :

- RefreshAccessTokenUseCase

### Infrastructure

Persistance des refresh tokens.

### UI

Endpoints HTTP.

---

# ClientCredentials

```text
Auth/
└── ClientCredentials/
```

## Responsabilité

Authentification serveur à serveur.

Permet à une API ou une application d'obtenir un JWT technique.

## Exemple

```http
POST /api/v1/auth/client-token
```

## Cas d'usage

```text
Billing API
    ↓
Auth API

Users API
    ↓
Auth API
```

## Contenu

### Domain

Contrats métier des clients applicatifs.

### Application

Cas d'usage :

- GenerateClientTokenUseCase

### Infrastructure

Validation des secrets applicatifs.

### UI

Contrôleurs HTTP.

---

# SiteAccess

```text
Auth/
└── SiteAccess/
```

## Responsabilité

Gérer les droits d'accès des utilisateurs aux différents sites de la plateforme.

Exemples :

- music.corbisier.fr
- lescorbycats.fr
- corbisier.fr

Un utilisateur peut :

- accéder à plusieurs sites
- avoir des rôles différents selon chaque site
- être refusé sur certains sites

## Modèle métier

```text
User
Site
UserSiteAccess
```

## Exemple

```text
Jean
 ├── MUSIC          -> ADMIN
 ├── LESCORBYCATS   -> USER
 └── CORBISIER      -> aucun accès
```

## Contenu

### Domain

Entités et règles métier.

Exemples :

- Site
- UserSiteAccess
- Role

### Application

Cas d'usage :

- CanAccessSiteUseCase
- HasRoleOnSiteUseCase

### Infrastructure

Repositories Doctrine.

### UI

Endpoints d'administration des accès.

---

# Jwt

```text
Auth/
└── Jwt/
```

## Responsabilité

Gestion technique des JWT.

Ce dossier ne contient aucun métier.

## Pourquoi ?

Le JWT est un mécanisme technique.

Les règles métier restent dans :

```text
Login
RefreshToken
ClientCredentials
SiteAccess
```

## Contenu

### Infrastructure

Exemples :

- JwtTokenGenerator
- JwtTokenValidator
- JwtEncoder
- JwtDecoder

---

# Architecture finale

```text
src/
└── Auth/
    ├── Login/
    │   ├── Domain/
    │   ├── Application/
    │   ├── Infrastructure/
    │   └── UI/
    │
    ├── RefreshToken/
    │   ├── Domain/
    │   ├── Application/
    │   ├── Infrastructure/
    │   └── UI/
    │
    ├── ClientCredentials/
    │   ├── Domain/
    │   ├── Application/
    │   ├── Infrastructure/
    │   └── UI/
    │
    ├── SiteAccess/
    │   ├── Domain/
    │   ├── Application/
    │   ├── Infrastructure/
    │   └── UI/
    │
    └── Jwt/
        └── Infrastructure/
```

# Principe

Chaque dossier représente une fonctionnalité métier identifiable.

Le métier appartient aux fonctionnalités.

Le JWT reste un détail technique isolé.

Cette organisation permet de déplacer facilement une fonctionnalité complète vers une autre application Symfony sans dépendance forte au reste du projet.