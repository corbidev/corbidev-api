# Guide CRON avancé -- Symfony Log Queue

## 🧠 Rappel du format CRON

    * * * * * commande
    │ │ │ │ │
    │ │ │ │ └── Jour semaine (0–7)
    │ │ │ └──── Mois (1–12)
    │ │ └────── Jour (1–31)
    │ └──────── Heure (0–23)
    └────────── Minute (0–59)

------------------------------------------------------------------------

# 🚀 Ton architecture

    API → File Queue → CRON → DB

------------------------------------------------------------------------

# ⚙️ Commandes Symfony

## 1. Traitement principal

    php bin/console app:process-log-queue

👉 Lit les fichiers `queue_*.log`\
👉 Insert en base (batch)

------------------------------------------------------------------------

## 2. Retry / récupération

    php bin/console app:process-log-retry

👉 Traite les `.processing` bloqués\
👉 Renomme en `.failed` si erreur\
👉 Envoie mail

------------------------------------------------------------------------

# ⏱️ CRON recommandé

## 🔥 Traitement quasi temps réel

    * * * * * php /chemin/projet/bin/console app:process-log-queue >> /chemin/logs/process_queue.log 2>&1

👉 toutes les minutes\
👉 très fluide pour logs

------------------------------------------------------------------------

## 🌙 Retry nocturne

    0 2 * * * php /chemin/projet/bin/console app:process-log-retry >> /chemin/logs/process_retry.log 2>&1

👉 1 fois par jour

------------------------------------------------------------------------

# 📁 Logs CRON

    >> /chemin/logs/fichier.log 2>&1

👉 redirige : - stdout - erreurs

------------------------------------------------------------------------

# ⚠️ Bonnes pratiques

## 1. Toujours logguer

Sans ça → debug impossible

------------------------------------------------------------------------

## 2. Ne jamais faire tourner en boucle infinie

✔ ici → OK\
❌ Messenger worker → interdit sur IONOS

------------------------------------------------------------------------

## 3. Traiter en batch

✔ flush tous les 100\
✔ perf x100

------------------------------------------------------------------------

## 4. Nom des fichiers

    queue_YYYY-MM-DD-HHMMSS-micro.log

✔ tri naturel\
✔ unique

------------------------------------------------------------------------

## 5. Gestion crash

    queue.log → queue.log.processing

✔ évite double traitement

------------------------------------------------------------------------

## 6. Retry automatique

    .processing → retry → failed

✔ aucune perte de données

------------------------------------------------------------------------

# ❌ Erreurs à éviter

❌ flush dans boucle\
❌ accès DB dans API\
❌ fichier unique partagé\
❌ absence de logs cron\
❌ pas de retry

------------------------------------------------------------------------

# 📊 Exemple complet IONOS

    * * * * * /usr/bin/php /kunden/.../bin/console app:process-log-queue >> /kunden/.../logs/process_queue.log 2>&1
    0 2 * * * /usr/bin/php /kunden/.../bin/console app:process-log-retry >> /kunden/.../logs/process_retry.log 2>&1

------------------------------------------------------------------------

# 🏁 Résumé

✔ API rapide\
✔ traitement async\
✔ batch performant\
✔ retry sécurisé\
✔ compatible IONOS

------------------------------------------------------------------------

# 💬 Conclusion

Tu as maintenant une architecture proche de :

-   Sentry
-   Datadog (simplifié)

👉 robuste, scalable, propre
