
# 📁 STRATÉGIE FILESYSTEM ROBUSTE — QUEUE LOGGING

## 🎯 OBJECTIF

Garantir :
- aucun fichier corrompu
- aucune lecture partielle
- aucune collision
- aucune race condition
- tolérance aux crashs

---

# 🧠 PROBLÈMES À ÉVITER

## Écriture partielle
Fichier JSON incomplet → invalide

## Lecture pendant écriture
Cron lit un fichier en cours → crash

## Collision de nom
Deux fichiers identiques → overwrite

## Rename non fiable
Peut échouer ou ne pas être atomique

## Crash pendant write
Fichier laissé dans un état invalide

---

# 🧱 STRUCTURE DOSSIERS

queue/
  pending/
  processing/
  error/
  failed/

---

# 🧩 ÉCRITURE SAFE (QueueWriter)

## ❌ INTERDIT
Écrire directement dans pending

## ✅ STRATÉGIE

1. écrire dans un fichier temporaire (.tmp)
2. flush complet
3. rename vers pending

## FLOW

/tmp/file.tmp
   ↓
write OK
   ↓
rename
   ↓
queue/pending/file.queue

👉 .tmp = invisible
👉 .queue = toujours valide

---

# 🧩 NOM DE FICHIER

FORMAT :

YYYYMMDD_HHMMSS_microtime_random.queue

EXEMPLE :

20260430_120102_123456_ab12cd.queue

---

# 🧩 WRITE ATOMIQUE

tmp = file.tmp
final = file.queue

file_put_contents(tmp, json, LOCK_EX)
rename(tmp, final)

---

# 🧩 LECTURE SAFE (CRON)

## ❌ INTERDIT
Lire directement pending

## ✅ STRATÉGIE

pending → rename → processing → lecture

---

# 🧩 TRAITEMENT

SUCCESS :
processing → delete

FAIL :
processing → error

FAIL FINAL :
error → failed

---

# 🔥 ANTI RACE CONDITION

- rename = lock implicite
- aucun flock nécessaire
- un seul cron traite un fichier

---

# 🧩 SAFE RENAME PATTERNS

## ✅ RECOMMANDÉ
tmp → rename

## 🟡 ALTERNATIVE
tmp → copy → unlink

## ❌ INTERDIT
write direct

---

# 🧠 FLOW GLOBAL

WRITE :
tmp → pending

CRON :
pending → processing → success/delete
                        → error → retry
                                 → failed

---

# 🧪 TESTS OBLIGATOIRES

- fichier jamais partiel
- collision impossible
- double cron OK
- crash write safe
- tmp ignoré
- retry fonctionne

---

# ⚠️ CAS SPÉCIAL

processing bloqué :

si ancien → remettre en pending

---

# 🔒 BONNES PRATIQUES

- vérifier JSON avant write
- limiter taille fichier
- vérifier permissions

---

# 🧠 RÈGLES D’OR

1. jamais écrire directement
2. toujours tmp → rename
3. toujours déplacer avant lire
4. un fichier = une vérité
5. pas de lock complexe

---

# 🚀 CONCLUSION

Système :
✔ robuste
✔ simple
✔ sans dépendance
✔ production ready
