# SAFE FILESYSTEM — JOB DEFINITIONS (STRICT MODE)

## 🎯 OBJECTIF

Définir des opérations filesystem simples, atomiques et testables.
Aucune logique métier. Aucun comportement implicite.

---

## 🧱 ARCHITECTURE

src/
  Shared/
    Infrastructure/
      Filesystem/
        SafeFilesystem.php
        LocalSafeFilesystem.php
        FilesystemResult.php

tests/
  Shared/
    Filesystem/
      WriteAtomicTest.php
      MoveTest.php
      ReadTest.php
      DeleteTest.php
      ExistsTest.php
      ListTest.php

var/
  log/
    errorSystem/
      filesystem.log

---

## 🧩 JOBS + ENTRÉES / SORTIES

### 1. WRITE_ATOMIC

INPUT:
- targetPath (string)
- content (string)

SUCCESS OUTPUT:
- success = true

FAILURE OUTPUT:
- success = false
- error message

RULES:
- jamais de fichier partiel visible
- .tmp supprimé après succès

---

### 2. MOVE (STRICT)

INPUT:
- from (string)
- to (string)

SUCCESS OUTPUT:
- success = true

FAILURE OUTPUT:
- success = false
- error message

RULES:
- rename uniquement
- source n'existe plus après succès

---

### 3. READ

INPUT:
- path (string)

SUCCESS OUTPUT:
- success = true
- content (string)

FAILURE OUTPUT:
- success = false
- error message

RULES:
- refuse .tmp
- fichier doit exister

---

### 4. DELETE

INPUT:
- path (string)

SUCCESS OUTPUT:
- success = true

FAILURE OUTPUT:
- success = false
- error message

RULES:
- idempotent (si absent → success)

---

### 5. EXISTS

INPUT:
- path (string)

OUTPUT:
- bool

---

### 6. LIST

INPUT:
- directory (string)

SUCCESS OUTPUT:
- array<string>

FAILURE OUTPUT:
- empty array + log error

RULES:
- ignore .tmp

---

## ❌ EXCLUSIONS

- Retry
- TTL
- Queue logic
- JSON validation
- Business logic

---

## 🧪 TESTS

WRITE_ATOMIC:
- jamais de fichier partiel
- pas de .tmp après succès
- contenu exact

MOVE:
- fichier déplacé
- source supprimée

READ:
- ne lit pas .tmp
- contenu exact

DELETE:
- idempotent

LIST:
- ignore .tmp

---

## ⚠️ MODE STRICT

MOVE = rename uniquement
Aucun fallback

---

## 🧠 RÈGLES

- zéro exception exposée
- erreurs loggées
- comportements déterministes
