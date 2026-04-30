# HMAC Client PHP – Documentation

## 🎯 Objectif

Client PHP prêt à l'emploi pour appeler l'API sécurisée :

https://api.corbisier.fr/api/jwt

---

## 🔐 Principe

Chaque requête doit contenir :

- X-CLIENT-ID
- X-TIMESTAMP
- X-NONCE
- X-SIGNATURE

Signature basée sur :

METHOD
PATH
BODY_HASH
TIMESTAMP
NONCE

---

## ⚠️ IMPORTANT

Le PATH signé doit être EXACTEMENT :

/api/jwt/...

❌ Ne pas signer l'URL complète  
❌ Ne pas oublier /api

---

## 📄 Client PHP

```php
<?php

class HmacClient
{
    private string $clientId;
    private string $secret;
    private string $baseUrl;

    public function __construct(string $clientId, string $secret, string $baseUrl)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function request(string $method, string $path, array $body = []): array
    {
        $url = $this->baseUrl . $path;

        $jsonBody = empty($body) ? '' : json_encode($body, JSON_UNESCAPED_SLASHES);
        $bodyHash = hash('sha256', $jsonBody);

        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));

        $normalizedPath = rtrim($path, '/') ?: '/';

        $stringToSign = implode("\n", [
            strtoupper($method),
            $normalizedPath,
            $bodyHash,
            $timestamp,
            $nonce,
        ]);

        $signature = hash_hmac('sha256', $stringToSign, $this->secret);

        $headers = [
            'Content-Type: application/json',
            'X-CLIENT-ID: ' . $this->clientId,
            'X-TIMESTAMP: ' . $timestamp,
            'X-NONCE: ' . $nonce,
            'X-SIGNATURE: ' . $signature,
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $jsonBody ?: null,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \RuntimeException(curl_error($ch));
        }

        return [
            'status' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'body' => json_decode($response, true),
        ];
    }
}
```

---

## 🧩 Exemple

```php
$client = new HmacClient(
    'cli_xxxxx',
    'secret_xxxxx',
    'https://api.corbisier.fr'
);

$response = $client->request(
    'POST',
    '/api/jwt/clients',
    [
        'name' => 'Mon service'
    ]
);

print_r($response);
```

---

## ⚠️ Points critiques

### 1. Signature stricte

METHOD
PATH
BODY_HASH
TIMESTAMP
NONCE

---

### 2. Body brut JSON

- Pas de transformation
- Pas de tri

---

### 3. Path exact

/api/jwt/... obligatoire

---

### 4. Timestamp

Tolérance : ±5 minutes

---

## ✅ Résultat

- Signature valide
- API sécurisée
- Intégration rapide
