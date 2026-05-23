
# ⏱️ Command (Cron)

## Rôle
- lire queue
- persister
- gérer retry

## Flow
queue → success → delete  
queue → fail → .error → retry  
fail final → /failed + mail

## Mail
- natif (pas Symfony)
