# MIGRATION DOCTRINE

## Générer une migration

```bash
php bin/console doctrine:migrations:diff --em=auth
```

## Exécuter la migration

```bash
php bin/console doctrine:migrations:migrate --em=auth
```

## Valider le mapping

```bash
php bin/console doctrine:schema:validate --em=auth
```

## Créer le schéma directement (développement uniquement)

```bash
php bin/console doctrine:schema:create --em=auth
```

## Mettre à jour le schéma (développement uniquement)

```bash
php bin/console doctrine:schema:update --em=auth --dump-sql
```

ou

```bash
php bin/console doctrine:schema:update --em=auth --force
```
