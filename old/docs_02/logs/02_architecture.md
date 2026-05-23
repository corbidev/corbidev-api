
# 🧱 Architecture complète

## Blocs

1. API Externe
2. Adapter (DTO / Processor)
3. Shared Logging Core
4. Queue (filesystem)
5. Command (cron)
6. Persistence

## Flow global

API → DTO → Factory → LogEntry → Processor → Normalizer → Formatter → Queue  
Queue → Cron → Persister → Storage

## Règles

- sens unique
- aucune dépendance circulaire
- shared = cœur technique
