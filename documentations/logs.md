{
"logs":
[

{
  "externalId": "uuid",

  "domain": "corbisier.fr",
  "uri": "/login",
  "method": "POST",
  "ip": "192.168.234.135",

  "message": "User login failed",
  "level": "ERROR",
  "env": "prod",

  "client": "web",
  "version": "1.0.0",

  "fingerprint": "abc123",
  "userId": 42,
  "httpStatus": 401,

  "context": {
    "target": {
      "domain": "auth.corbisier.fr",
      "uri": "/login"
    }
  }
}
]
}

crontab -e


# Traitement toutes les heures
0 * * * * /usr/bin/php /kunden/homepages/46/d451601943/htdocs/corbisier.fr/dev/symfony_api/api/bin/console app:logs:consume --limit=50 --time-limit=300 --memory-limit=128M >> /kunden/homepages/46/d451601943/logs/consume.log 2>&1

# Retry des erreurs (1 fois par jour à 2h)
0 2 * * * /usr/bin/php /kunden/homepages/46/d451601943/htdocs/corbisier.fr/dev/symfony_api/api/bin/console app:logs:retry --limit=50 --time-limit=300 --memory-limit=128M >> /kunden/homepages/46/d451601943/logs/retry.log 2>&1

:qw

⚠️ Conseils importants
🔹 1. Toujours tester avant
Bash

`
/usr/bin/php /kunden/homepages/46/d451601943/htdocs/bin/console app:logs:consume
`

🔹 2. Vérifier PHP
Bash
`
which php
`