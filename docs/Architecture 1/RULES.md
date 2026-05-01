# GLOBAL RULES

## GENERAL

- simplicité
- contrôle total
- pas d’async
- pas de logique cachée

## PHPDOC

- obligatoire
- en français
- expliquer le pourquoi

## NAMING

BON :
- AuthTokenGenerator
- LoginValidator

MAUVAIS :
- UtilsService
- HelperManager

## NO HIDDEN LOGIC

- wrapper obligatoire des bundles
- config sans logique métier
- code explicite uniquement

## LOGGING

- request_id obligatoire
- logs structurés
- pas de données sensibles
