# RessLogs - Detail des fichiers

## Objectif du module

Le dossier `api/src/RessLogs` contient le module de collecte et de persistence des logs applicatifs recus par l'endpoint `POST /api/logs`.

Le flux actuel est le suivant :

1. le controleur recoit la requete HTTP ;
2. le JSON est decode ;
3. le mapper transforme le tableau JSON en DTO ;
4. le service metier valide et convertit ce DTO en entites Doctrine ;
5. les repositories servent a lire ou nettoyer les donnees en base ;
6. les entites representent les tables SQL utilisees par le module.

## Arborescence

- `Controller` : point d'entree HTTP du module.
- `Dto` : objet de transport entre la couche HTTP et la couche metier.
- `Mapper` : transformation du JSON recu vers le DTO applicatif.
- `Service` : orchestration metier et persistence des logs.
- `Entity` : modeles Doctrine relies aux tables SQL.
- `Repository` : acces aux entites et requetes de persistence specialisees.
- `ApiResource` : dossier reserve a de futures ressources API Platform ; actuellement vide hormis `.gitignore`.

## Controller

### `api/src/RessLogs/Controller/CreateLogController.php`

Role : point d'entree HTTP pour la creation d'un log.

Responsabilites :

- expose la route `POST /api/logs` ;
- decode le corps JSON ;
- rejette les payloads invalides ou non objets ;
- exige `Authorization: Bearer ...` et resolve la source depuis le JWT ;
- rejette `sourceApiKey` et `sourceId` s'ils sont presents dans le body JSON, car la source est deja resolue via le Bearer JWT ;
- delegue la transformation du payload au mapper ;
- delegue l'enregistrement metier au service `LogRecorderInterface` ;
- retourne une reponse JSON `201` en cas de succes ou `400` en cas d'erreur fonctionnelle.

Ce fichier ne parle pas directement a Doctrine et ne construit pas lui-meme les entites SQL.

## DTO

### `api/src/RessLogs/Dto/CreateLogRequestDto.php`

Role : contrat de donnees interne entre la couche HTTP et la couche metier.

Responsabilites :

- porte les champs issus du JSON d'entree ;
- centralise le format attendu par le service d'enregistrement ;
- evite de faire circuler un tableau PHP brut dans le coeur metier.

Champs notables :

- `message`, `title`, `url`, `uri` ;
- `httpStatus`, `durationMs`, `fingerprint`, `context` ;
- `ts`, `createdAt` ;
- `level`, `env` ;
- `sourceId`, `sourceApiKey` ;
- `urlId`, `uriId`, `routeId`, `url`, `uri` ;
- `tags`.

Le DTO transporte la donnee ; il ne contient pas de logique metier.

## Mapper

### `api/src/RessLogs/Mapper/CreateLogRequestMapperInterface.php`

Role : contrat du mapper qui convertit le payload JSON en DTO.

Responsabilites :

- formaliser le point d'entree de transformation ;
- permettre de remplacer l'implementation sans changer le controleur.

### `api/src/RessLogs/Mapper/CreateLogRequestMapper.php`

Role : implementation concrete du mapping entre tableau JSON decode et `CreateLogRequestDto`.

Responsabilites :

- normaliser certaines valeurs simples ;
- convertir les champs numeriques optionnels ;
- injecter l'identite source resolue depuis le JWT valide ;
- produire un DTO coherent pour la couche service.

Ce fichier fait de la preparation de donnees, pas de persistence et pas de validation metier approfondie.

## Service

### `api/src/RessLogs/Service/LogRecorderInterface.php`

Role : contrat du service metier qui enregistre un log.

Responsabilites :

- definir la methode `record(CreateLogRequestDto $request): LogEntry` ;
- abstraire le service concret vis-a-vis du controleur.

### `api/src/RessLogs/Service/LogRecorder.php`

Role : service metier principal du module.

Responsabilites :

- valider les champs obligatoires comme `message` et `url` ;
- verifier que `url` est une URL standard `http` ou `https` ;
- resoudre les references metier `level`, `env` et `source` ;
- resoudre le couple `url` / `uri` via les IDs ou les valeurs textuelles ;
- normaliser une `url` contenant deja un path en deplacant ce path vers `uri` ;
- rejeter la requete si `url` contient un path incompatible avec `uri` ;
- creer au besoin les entites `LogUrl`, `LogUri` et `LogTag` ;
- construire une entite `LogEntry` ;
- persister le log et les relations associees ;
- demander aux repositories `LogUriRepository` et `LogUrlRepository` de supprimer les orphelins.

Ce fichier fait le lien entre le DTO applicatif et le modele Doctrine. La source resolue est desormais `App\RessAuth\Entity\AuthCredential`.

Note schema : toutes les tables SQL du projet sont prefixees par la variable `SQL_PREFIXE` definie dans `.env`.

## Entities

### `api/src/RessLogs/Entity/LogEntry.php`

Role : entite principale du module, correspondant a la table `${SQL_PREFIXE}log_entry`.

Responsabilites :

- stocker un evenement de log ;
- porter les donnees metier centrales : message, titre, horodatage, statut HTTP, duree, fingerprint, contexte ;
- porter les relations vers le niveau, l'environnement, la source canonique (`AuthCredential`), l'URL et l'URI ;
- porter la collection des tags via `LogEntryTag`.

Particularites :

- plusieurs index Doctrine sont declares pour faciliter les recherches par date, niveau, source, environnement et fingerprint ;
- `entryTags` est configure avec `cascade` et `orphanRemoval`.

### `api/src/RessLogs/Entity/LogEntryTag.php`

Role : table de jointure entre un log et un tag.

Responsabilites :

- relier `LogEntry` et `LogTag` en relation plusieurs-a-plusieurs explicite ;
- servir de pivot Doctrine vers la table `${SQL_PREFIXE}log_entry_tag`.

Particularite : la cle primaire est composite, composee de `log_entry_id` et `tag_id`.

### `api/src/RessLogs/Entity/LogEnv.php`

Role : referentiel des environnements techniques.

Responsabilites :

- stocker des valeurs comme `dev`, `test`, `prod` ;
- etre relie a plusieurs `LogEntry`.

### `api/src/RessLogs/Entity/LogLevel.php`

Role : referentiel des niveaux de log.

Responsabilites :

- stocker des valeurs comme `debug`, `info`, `warning`, `error`, `critical` ;
- etre relie a plusieurs `LogEntry`.

### `api/src/RessAuth/Entity/AuthCredential.php`

Role : representer la source canonique de l'application, y compris son identite metier et son secret d'authentification.

Responsabilites :

- stocker le nom, le type et la cle API de la source ;
- stocker le hash du secret client ;
- indiquer si la source est active ;
- relier cette source a tous les logs emis par elle via `LogEntry.source_id`.

Champ important : `apiKey` est utilisee par le service pour identifier la source si `sourceId` n'est pas fourni.

### `api/src/RessAuth/Command/SetLogSourceSecretCommand.php`

Role : commande Symfony d'administration pour creer ou mettre a jour une source canonique et son secret.

Responsabilites :

- rechercher une source par `apiKey` dans `${SQL_PREFIXE}auth_credential` ;
- creer la source si elle n'existe pas encore ;
- generer ou accepter un secret ;
- stocker le hash Argon2id du secret ;
- conserver la compatibilite de nom de commande via `app:logs:set-source-secret`.

### `api/src/RessLogs/Entity/LogTag.php`

Role : referentiel des tags de classification.

Responsabilites :

- stocker un nom de tag unique ;
- relier ce tag a plusieurs associations `LogEntryTag`.

### `api/src/RessLogs/Entity/LogUrl.php`

Role : referentiel des URL completes.

Responsabilites :

- stocker une URL absolue unique ;
- relier cette URL a plusieurs `LogUri` ;
- relier cette URL a plusieurs `LogEntry`.

Le service cree cette entite si l'URL transmise n'existe pas encore.

### `api/src/RessLogs/Entity/LogUri.php`

Role : referentiel des URI ou chemins applicatifs.

Responsabilites :

- stocker un chemin unique comme `/api/users` ;
- maintenir le rattachement a une `LogUrl` ;
- relier cette URI a plusieurs `LogEntry`.

Particularite : une `LogUri` doit etre rattachee a une `LogUrl`.

## Repositories

### `api/src/RessLogs/Repository/LogEntryRepository.php`

Role : repository Doctrine de l'entite `LogEntry`.

Responsabilites actuelles :

- fournir les methodes standards de `ServiceEntityRepository` pour les logs.

### `api/src/RessLogs/Repository/LogEntryTagRepository.php`

Role : repository Doctrine de l'entite `LogEntryTag`.

Responsabilites actuelles :

- fournir les methodes standards Doctrine sur la table de jointure log-tag.

### `api/src/RessLogs/Repository/LogEnvRepository.php`

Role : repository Doctrine de l'entite `LogEnv`.

Responsabilites actuelles :

- permettre la recherche des environnements par identifiant ou par nom.

### `api/src/RessLogs/Repository/LogLevelRepository.php`

Role : repository Doctrine de l'entite `LogLevel`.

Responsabilites actuelles :

- permettre la recherche des niveaux de log par identifiant ou par nom.

### `api/src/RessAuth/Repository/AuthCredentialRepository.php`

Role : repository Doctrine de l'entite `AuthCredential`.

Responsabilites actuelles :

- permettre la recherche d'une source canonique active par `apiKey` ;
- centraliser l'acces Doctrine a la table `${SQL_PREFIXE}auth_credential`.

### `api/src/RessLogs/Repository/LogTagRepository.php`

Role : repository Doctrine de l'entite `LogTag`.

Responsabilites actuelles :

- permettre la recherche d'un tag par identifiant ou par nom.

### `api/src/RessLogs/Repository/LogUriRepository.php`

Role : repository Doctrine de l'entite `LogUri`.

Responsabilites actuelles :

- permettre la recherche d'une URI par identifiant ou par valeur ;
- supprimer les URI orphelines via `deleteOrphans()` apres l'enregistrement d'un log.

### `api/src/RessLogs/Repository/LogUrlRepository.php`

Role : repository Doctrine de l'entite `LogUrl`.

Responsabilites actuelles :

- permettre la recherche d'une URL par identifiant ou par valeur ;
- supprimer les URL orphelines via `deleteOrphans()` apres l'enregistrement d'un log.

## ApiResource

### `api/src/RessLogs/ApiResource/.gitignore`

Role : conserver le dossier dans le depot Git meme s'il ne contient encore aucune ressource API Platform.

Responsabilites actuelles :

- aucune logique applicative ;
- simple fichier technique de maintien du dossier.

## Resume architectural

Le module est actuellement organise en couches simples :

- `CreateLogController` gere HTTP ;
- `CreateLogRequestMapper` transforme les donnees recues ;
- `CreateLogRequestDto` transporte les donnees ;
- `LogRecorder` execute la logique metier et cree les entites ;
- les `Entity` representent la structure SQL ;
- les `Repository` encapsulent l'acces Doctrine et certaines operations de nettoyage.

Cette organisation permet d'eviter qu'un JSON brut arrive directement jusqu'a Doctrine, tout en gardant une separation claire entre transport, metier et persistence.