# Commandes Doctrine Symfony (complètes)

## 🔧 Base de données

-   `php bin/console doctrine:database:create`\
    → Crée la base de données

-   `php bin/console doctrine:database:drop --force`\
    → Supprime la base de données

------------------------------------------------------------------------

## 🧱 Schéma (tables)

-   `php bin/console doctrine:schema:create`\
    → Crée les tables directement depuis les entités

-   `php bin/console doctrine:schema:update --force`\
    → Met à jour les tables selon les entités (rapide, mais à éviter en
    prod)

-   `php bin/console doctrine:schema:validate`\
    → Vérifie si les entités et la base sont synchronisées

------------------------------------------------------------------------

## 📦 Migrations (recommandé)

### Génération

-   `php bin/console doctrine:migrations:diff`\
    → Génère un fichier de migration basé sur les changements d'entités

-   `php bin/console make:migration`\
    → Génère une migration (équivalent moderne avec MakerBundle)

------------------------------------------------------------------------

### Exécution

-   `php bin/console doctrine:migrations:migrate`\
    → Exécute les migrations

-   `php bin/console doctrine:migrations:execute --up <version>`\
    → Exécute une migration spécifique

-   `php bin/console doctrine:migrations:execute --down <version>`\
    → Annule une migration spécifique

------------------------------------------------------------------------

### Gestion

-   `php bin/console doctrine:migrations:status`\
    → Affiche l'état des migrations

-   `php bin/console doctrine:migrations:list`\
    → Liste toutes les migrations

-   `php bin/console doctrine:migrations:version --add --all`\
    → Marque toutes les migrations comme exécutées (sans les lancer)

-   `php bin/console doctrine:migrations:version --delete --all`\
    → Réinitialise l'état des migrations

------------------------------------------------------------------------

## 🌱 Données (fixtures)

-   `php bin/console doctrine:fixtures:load`\
    → Charge des données de test

-   `php bin/console doctrine:fixtures:load --append`\
    → Ajoute sans supprimer les données existantes

------------------------------------------------------------------------

## 🧹 Bonus utiles

-   `php bin/console doctrine:cache:clear-metadata`\
    → Vide le cache des métadonnées Doctrine

-   `php bin/console doctrine:query:sql "SELECT * FROM table"`\
    → Exécute une requête SQL directe

-   `php bin/console doctrine:query:dql "SELECT e FROM App\Entity\User e"`\
    → Exécute une requête DQL

------------------------------------------------------------------------

## ⚡ Workflows utiles

### 🔁 Recréation complète de la base

``` bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

------------------------------------------------------------------------

### 🧪 Workflow développement classique

1.  Modifier les entités\
2.  `php bin/console make:migration`\
3.  `php bin/console doctrine:migrations:migrate`

------------------------------------------------------------------------

### 🚨 Cas fréquent (migration vide)

Si `diff` ne génère rien : - Vérifier `doctrine:schema:validate` -
Vérifier mapping (annotations / attributes) - Vérifier que l'entité est
bien prise en compte

------------------------------------------------------------------------

### 🧠 Astuce

Toujours privilégier **migrations** plutôt que `schema:update` en
production.
