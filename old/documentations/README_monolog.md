🧠 Logging centralisé avec Monolog (Auto)

Ce système permet de logger automatiquement toutes les APIs vers un service central "api_logs", sans effort côté développeur.

---

🎯 Objectif

- Centraliser les logs de toutes les APIs
- Ne rien écrire manuellement (auto-enrichissement)
- Uniformiser le format des logs
- Permettre monitoring, debug et analyse

---

🏗️ Architecture

[ Symfony App ]
       ↓
   Monolog
       ↓
 Processor (enrichissement auto)
       ↓
 Handler (buffer + HTTP)
       ↓
   API_LOGS

---

⚙️ Fonctionnement global

Quand tu écris :

$logger->error('User login failed');

👉 Le système :

1. Ajoute automatiquement :
   
   - userId
   - client (auth-api, admin-api…)
   - requestId
   - httpStatus
   - ip / uri / method

2. Formate le log

3. Le bufferise

4. L’envoie à "api_logs"

---

🔥 Résultat envoyé

{
  "logs": [
    {
      "externalId": "uuid",
      "domain": "corbisier.fr",
      "uri": "/login",
      "method": "POST",
      "ip": "192.168.1.1",

      "message": "User login failed",
      "level": "ERROR",
      "env": "prod",

      "client": "auth-api",
      "version": "1.0.0",

      "fingerprint": "abc123",
      "userId": 42,
      "httpStatus": 401,
      "requestId": "req_123",

      "context": {}
    }
  ]
}

---

🚀 Utilisation

✅ Log simple

$logger->error('User login failed');

✅ Log avec contexte

$logger->warning('Payment refused', [
    'httpStatus' => 402,
]);

👉 Rien d’autre à faire.

---

🧠 Enrichissement automatique

Champ| Source
userId| Security Symfony
client| URL / route
requestId| Header ou généré
httpStatus| Response / Exception
domain| Request
uri| Request
method| Request
ip| Request

---

🔀 Multi-API / Multisite

Le champ "client" est automatiquement déterminé :

URL| client
"/auth/login"| "auth-api"
"/admin/users"| "admin-api"
"/api/products"| "api"
autre| "main-api"

---

⚙️ Configuration requise

".env"

API_LOGS_ENDPOINT=https://api-log.corbisier.fr/logs
APP_VERSION=1.0.0

---

🧩 Composants

1. Processor

👉 enrichit automatiquement les logs

LogContextProcessor

---

2. Handler

👉 buffer + envoi HTTP

BufferedApiLogsHandler

---

3. Context Provider

👉 fournit les données HTTP

LogContextProvider

---

4. Subscribers

- HttpStatusSubscriber → injecte le status HTTP
- LogFlushSubscriber → envoi des logs après réponse

---

⚠️ Bonnes pratiques

❌ Ne pas faire

- logger les passwords
- logger les tokens
- ajouter "client" manuellement
- appeler l’API logs directement

---

✅ Faire

- utiliser "$logger"
- laisser le système enrichir
- utiliser le context uniquement pour métier

---

🛡️ Sécurité

Les champs sensibles sont automatiquement supprimés :

- password
- token

---

⚡ Performance

- bufferisation (batch)
- envoi en fin de requête
- non bloquant utilisateur

---

🧪 Test rapide

$logger->error('Test log');

👉 vérifier :

- envoi vers API_LOGS
- présence des champs enrichis

---

🧠 Avantages

- zéro configuration par API
- centralisation totale
- multi-API natif
- compatible mutualisé
- extensible (ELK, monitoring…)

---

✅ Conclusion

👉 Ce système fournit un logging automatique, structuré et centralisé, prêt pour la production et le scaling.

---
