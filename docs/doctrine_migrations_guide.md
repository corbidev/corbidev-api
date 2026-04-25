# 🧠 Doctrine Migrations — Guide Simple & Fiable

## ✅ Cas standard (recommandé)

### 1. Générer une migration
php bin/console make:migration

✔ Compare Entities ↔ Base de données  
✔ Génère un fichier dans /migrations

---

### 2. Vérifier l’état
php bin/console doctrine:migrations:status

---

### 3. Appliquer la migration
php bin/console doctrine:migrations:migrate

---

## ⚠️ Cas fréquent (modifications non détectées)

Après modification d’une Entity (ex: LogEvent)

php bin/console doctrine:schema:validate

Puis :

php bin/console doctrine:migrations:diff

---

## 🔍 Voir ce que Doctrine veut faire

php bin/console doctrine:schema:update --dump-sql

✔ Très utile pour debug  
✔ Permet de comprendre les écarts

---

## ⚡ Mise à jour rapide (DEV uniquement)

php bin/console doctrine:schema:update --force

⚠️ Sans migration  
⚠️ À éviter en production

---

## 💣 Reset complet (DEV uniquement)

php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

✔ Repart de zéro  
⚠️ Supprime toutes les données

---

## 🎯 Cas concret (LogEvent)

Si tu ajoutes :

- createdAt
- eventAt

Ta migration doit contenir :

ALTER TABLE CBV_LOGS_EVENT ADD createdAt DATETIME NOT NULL;
ALTER TABLE CBV_LOGS_EVENT ADD eventAt DATETIME DEFAULT NULL;

---

## 🧠 Bonne pratique (important)

Toujours vérifier avant migration :

php bin/console doctrine:schema:validate

---

## 🚀 TL;DR

php bin/console make:migration
php bin/console doctrine:migrations:migrate

Si problème :

php bin/console doctrine:schema:update --dump-sql
