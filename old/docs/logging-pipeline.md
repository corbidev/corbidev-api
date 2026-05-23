# 📊 Logging Pipeline

## 🧠 Architecture

Client → API → File Queue → Worker → DB

---

## 🔁 Idempotence

- externalId unique
- duplicate = ignoré
- aucun blocage DB

---

## 📁 Files

- var/log_queue/ → input
- var/log_queue_errors/ → erreurs

---

## ⚙️ Commands

php bin/console app:process-log-queue
php bin/console app:process-log-retry

---

## 🚨 Error Handling

- erreurs loggées en JSON
- jamais d’exception bloquante
- retry automatique possible

---

## 🕒 Timestamp

- UTC strict
- priorité :
  1. timestamp client
  2. timestamp fichier
  3. now()

---

## 🔗 Request ID

- transmis via X-Request-Id
- généré si absent
