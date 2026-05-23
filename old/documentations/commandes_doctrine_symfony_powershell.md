# Commandes Doctrine Symfony (complètes)

## 🔧 Base de données

``` powershell
php bin/console doctrine:database:create
```

→ Crée la base de données

``` powershell
php bin/console doctrine:database:drop --force
```

→ Supprime la base de données

------------------------------------------------------------------------

## 🧱 Schéma (tables)

``` powershell
php bin/console doctrine:schema:create
```

→ Crée les tables directement depuis les entités

``` powershell
php bin/console doctrine:schema:update --force
```

→ Met à jour les tables selon les entités (rapide, mais à éviter en
prod)

``` powershell
php bin/console doctrine:schema:validate
```

→ Vérifie si les entités et la base sont synchronisées

------------------------------------------------------------------------

## 📦 Migrations (recommandé)

### Génération

``` powershell
php bin/console doctrine:migrations:diff
```

→ Génère un fichier de migration basé sur les changements d'entités

``` powershell
php bin/console make:migration
```

→ Génère une migration (équivalent moderne avec MakerBundle)

------------------------------------------------------------------------

### Exécution

``` powershell
php bin/console doctrine:migrations:migrate
```

→ Exécute les migrations

``` powershell
php bin/console doctrine:migrations:execute --up <version>
```

→ Exécute une migration spécifique

``` powershell
php bin/console doctrine:migrations:execute --down <version>
```

→ Annule une migration spécifique

------------------------------------------------------------------------

### Gestion

``` powershell
php bin/console doctrine:migrations:status
```

→ Affiche l'état des migrations

``` powershell
php bin/console doctrine:migrations:list
```

→ Liste toutes les migrations

``` powershell
php bin/console doctrine:migrations:version --add --all
```

→ Marque toutes les migrations comme exécutées (sans les lancer)

``` powershell
php bin/console doctrine:migrations:version --delete --all
```

→ Réinitialise l'état des migrations

------------------------------------------------------------------------

## 🌱 Données (fixtures)

``` powershell
php bin/console doctrine:fixtures:load
```

→ Charge des données de test

``` powershell
php bin/console doctrine:fixtures:load --append
```

→ Ajoute sans supprimer les données existantes

------------------------------------------------------------------------

## 🧹 Bonus utiles

``` powershell
php bin/console doctrine:cache:clear-metadata
```

→ Vide le cache des métadonnées Doctrine

``` powershell
php bin/console doctrine:query:sql "SELECT * FROM table"
```

→ Exécute une requête SQL directe

``` powershell
php bin/console doctrine:query:dql "SELECT e FROM App\Entity\User e"
```

→ Exécute une requête DQL

------------------------------------------------------------------------

## ⚡ Workflows utiles

### 🔁 Recréation complète de la base

``` powershell
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

------------------------------------------------------------------------

### 🧪 Workflow développement classique

``` powershell
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

------------------------------------------------------------------------

### 🚨 Cas fréquent (migration vide)

``` powershell
php bin/console doctrine:schema:validate
```

→ Si rien ne change : - Vérifier mapping (annotations / attributes) -
Vérifier que l'entité est bien prise en compte

------------------------------------------------------------------------

### 🧠 Astuce

Toujours privilégier **migrations** plutôt que `schema:update` en
production.
