# 📚 Documentation Projet

## 🔧 Backend Symfony

- [Doctrine Migrations](./doctrine-migrations.md)
- [Logging Pipeline](./logging-pipeline.md)
- [Queue System](./queue-system.md)

---

## 🎯 Objectifs

- Logs idempotents
- Pipeline robuste (queue + retry)
- Zéro blocage DB
- Observabilité complète

---

## 🚀 Commandes utiles

php bin/console app:process-log-queue
php bin/console app:process-log-retry
