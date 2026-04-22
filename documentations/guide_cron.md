# Guide CRON simple

## Format d'une ligne CRON

    * * * * * commande
    │ │ │ │ │
    │ │ │ │ └── Jour de la semaine (0–7)
    │ │ │ └──── Mois (1–12)
    │ │ └────── Jour du mois (1–31)
    │ └──────── Heure (0–23)
    └────────── Minute (0–59)

------------------------------------------------------------------------

## Exemple principal

    * * * * * /chemin/commande

👉 Exécuté **toutes les minutes**

------------------------------------------------------------------------

## Exemples utiles

### Toutes les minutes

    * * * * *

### Toutes les heures

    0 * * * *

### Tous les jours à 2h

    0 2 * * *

### Tous les lundis à 3h

    0 3 * * 1

### Toutes les 5 minutes

    */5 * * * *

------------------------------------------------------------------------

## Astuces

-   `*` = toutes les valeurs
-   Ordre : minute → heure → jour → mois → semaine
-   Possibilité de plages :

```{=html}
<!-- -->
```
    0 9-18 * * 1-5

(9h à 18h, du lundi au vendredi)

------------------------------------------------------------------------

## Exemple pour ton projet

### Traitement queue (toutes les minutes)

    * * * * * php bin/console app:process-log-queue

### Retry (tous les jours à 2h)

    0 2 * * * php bin/console app:process-log-retry

------------------------------------------------------------------------

## Résumé

`* * * * *` = exécution chaque minute
