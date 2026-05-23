# 📁 File Queue System

## 🧠 Fonctionnement

1. Logs écrits en fichiers JSON
2. Worker lit les fichiers
3. Rename .processing
4. Traitement batch
5. Delete ou .error

---

## 📂 Structure

var/log_queue/
var/log_queue_errors/

---

## 🔄 Retry

- fichiers .processing > 24h
- retraitement automatique

---

## 🚀 Avantages

- résilient
- scalable
- simple à debug
