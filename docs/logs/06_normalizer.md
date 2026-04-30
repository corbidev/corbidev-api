
# 🧼 Normalizer

## Objectif
Rendre toutes les données sérialisables

## Limites
- profondeur: 5
- items: 50
- string: 1000

## Cas gérés
- recursion → [circular]
- object → [object Class]
- exception → structure
- resource → [resource]

## Sécurité
- filtrage password/token
- jamais d'exception
