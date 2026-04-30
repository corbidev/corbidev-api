📥 API_LOGS — Réception & Stockage des logs

Cette API centralise les logs provenant de toutes les applications (auth, API, admin, etc.).

---

🎯 Objectif

- Recevoir les logs via HTTP
- Valider leur structure
- Les stocker en base
- Permettre leur exploitation (monitoring, debug, analytics)

---

🏗️ Architecture

[ Apps Symfony ]
        ↓
 POST /logs
        ↓
[ API_LOGS ]
        ↓
[ Validation ]
        ↓
[ Stockage DB ]
        ↓
[ Analyse / Monitoring ]

---

🌐 Endpoint principal

POST /logs
Content-Type: application/json

---

📦 Format attendu

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

✅ Validation

Chaque log doit contenir au minimum :

Champ| Obligatoire
externalId| ✔
message| ✔
level| ✔

---

🧠 Règles métier

- "externalId" doit être unique
- "level" ∈ [DEBUG, INFO, WARNING, ERROR, CRITICAL]
- "context" est optionnel
- "userId" peut être null
- "httpStatus" recommandé

---

💾 Stockage

Table principale : "logs"

CREATE TABLE logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(36),
    domain VARCHAR(255),
    uri TEXT,
    method VARCHAR(10),
    ip VARCHAR(45),

    message TEXT,
    level VARCHAR(20),
    env VARCHAR(20),

    client VARCHAR(50),
    version VARCHAR(20),

    fingerprint VARCHAR(64),
    user_id BIGINT NULL,
    http_status INT NULL,
    request_id VARCHAR(50),

    context JSON NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

---

⚡ Optimisations recommandées

Index

CREATE INDEX idx_level ON logs(level);
CREATE INDEX idx_http_status ON logs(http_status);
CREATE INDEX idx_client ON logs(client);
CREATE INDEX idx_created_at ON logs(created_at);
CREATE INDEX idx_fingerprint ON logs(fingerprint);

---

🔥 Traitement des logs

Étapes

1. Validation du JSON
2. Transformation DTO
3. Normalisation
4. Persistance

---

🧩 Exemple Controller

public function __invoke(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    foreach ($data['logs'] as $log) {
        $this->logService->create($log);
    }

    return new JsonResponse(['status' => 'ok']);
}

---

🧠 Bonnes pratiques

✔ Faire

- valider les données
- filtrer les champs sensibles
- indexer la base
- gérer le volume

---

❌ Ne pas faire

- stocker passwords
- stocker tokens
- accepter des logs non structurés

---

🛡️ Sécurité

- limiter la taille des payloads
- ajouter authentification (token API)
- rate limiting recommandé

---

🚀 Évolutions possibles

🔥 Batch processing

- insertion en masse (bulk insert)

🔥 Queue

- file d’attente (RabbitMQ / Redis)

🔥 Export

- ELK / OpenSearch

🔥 Alerting

- erreurs 500 en temps réel

---

📊 Exploitation

Exemples :

- erreurs par API
- taux de 500
- endpoints les plus utilisés
- logs par utilisateur

---

🧪 Test rapide

curl -X POST http://localhost:8001/logs \
  -H "Content-Type: application/json" \
  -d '{"logs":[{"message":"test","level":"INFO","externalId":"123"}]}'

---

✅ Conclusion

"api_logs" est le cœur du système :

- centralisation ✔
- normalisation ✔
- analyse ✔

👉 Il permet de transformer tes logs en outil de pilotage réel.

---
