# 🧠 Doctrine Migrations

## ✅ Workflow standard

php bin/console make:migration
php bin/console doctrine:migrations:migrate

---

## 🔍 Debug

php bin/console doctrine:schema:validate
php bin/console doctrine:schema:update --dump-sql

---

## ⚠️ DEV uniquement

php bin/console doctrine:schema:update --force

---

## 💣 Reset DB

php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

---

## 🎯 Bonnes pratiques

- Toujours valider le schéma
- Ne jamais utiliser --force en prod
- Vérifier les migrations générées
