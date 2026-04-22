crontab -e

⏱️ 1. Traitement des logs (queue)
👉 toutes les heures (comme avant)

0 * * * * /usr/bin/php /kunden/homepages/46/d451601943/htdocs/corbisier.fr/dev/symfony_api/api/bin/console app:process-log-queue >> /kunden/homepages/46/d451601943/logs/process_queue.log 2>&1

🌙 2. Retry des .processing
👉 1 fois par jour à 2h

0 2 * * * /usr/bin/php /kunden/homepages/46/d451601943/htdocs/corbisier.fr/dev/symfony_api/api/bin/console app:process-log-retry >> /kunden/homepages/46/d451601943/logs/process_retry.log 2>&1