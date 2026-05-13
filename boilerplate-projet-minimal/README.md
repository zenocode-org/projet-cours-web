# Gabarit minimal de projet (PHP + front statique + Docker)

Ce dossier est un **point de départ** pour ton projet du module : structure légère, **une route API** qui écrit en base (`POST /api/exemples`), **deux routes factices** (`GET /api/healthcheck`, `GET /api/fake`), base **MySQL 8**, Apache en conteneur, et une page HTML avec **Tailwind** (via CDN). Tu peux le **dupliquer** ou t’en inspirer pour créer ton propre dépôt.

> **Ce que le [guide officiel](../README.md) ne détaille pas toujours** : *comment* le fichier SQL est exécuté automatiquement au premier lancement, et *comment repartir de zéro* si la base est dans un état incohérent. C’est décrit plus bas — lis cette section avant de te demander pourquoi ton `init.sql` « ne s’applique pas ».

---

## Contenu du gabarit

| Élément | Rôle |
|--------|------|
| `frontend/` | HTML, CSS, JS (vanilla). Tailwind chargé par CDN dans `index.html`. |
| `backend/public/api/` | API PHP : `POST /api/exemples` (insert dans la table `exemple`) ; `GET /api/healthcheck` et `GET /api/fake` → `{}`. |
| `backend/public/api/db.php` | Connexion **PDO MySQL** partagée (`DB_*` du conteneur). |
| `backend/public/api/exemples.php` | Handler **POST** : corps JSON `{"libelle": "..."}` (optionnel, max 255 car.) → **201** `{"id", "libelle"}`. |
| `database/init.sql` | **Seul** script d’init versionné : schéma minimal — à remplacer par ton modèle ; monté dans le conteneur MySQL (`/docker-entrypoint-initdb.d/`). |
| `backend/Dockerfile` | Image PHP 8.2 + Apache + extensions **PDO MySQL**. |
| `backend/apache.conf` | Racine web = front ; API servie sous `/api/`. |
| `docker-compose.yml` | Services **php**, **db** (MySQL 8), **phpmyadmin** (interface web pour la BDD). |

### phpMyAdmin

**phpMyAdmin** est l’interface web livrée avec ce gabarit : ouvre [http://localhost:8081](http://localhost:8081). Les variables `PMA_*` du `docker-compose.yml` pointent déjà vers le service `db` ; avec la configuration d’exemple, tu peux te connecter avec l’utilisateur **root** et le mot de passe défini par `MYSQL_ROOT_PASSWORD` (dans l’exemple : `root`), puis sélectionner la base `MYSQL_DATABASE` (ici `example`). Adapte ces valeurs si tu modifies `docker-compose.yml` ou un fichier `.env` que ton PHP lit pour `DB_*`.

---

## Démarrage rapide

Prérequis : **Docker** et **Docker Compose** installés.

```bash
cd boilerplate-projet-minimal
cp .env.example .env
# Édite .env si ton code PHP lit ces variables ; aligne-les avec DB_HOST / DB_* du compose
docker compose up -d --build
```

Ensuite :

- **Site** : [http://localhost:8080](http://localhost:8080) (page d’accueil + bouton de test `fetch`).
- **API** :
  - [http://localhost:8080/api/healthcheck](http://localhost:8080/api/healthcheck) et [http://localhost:8080/api/fake](http://localhost:8080/api/fake) — `GET`, réponse `{}`.
  - **POST** [http://localhost:8080/api/exemples](http://localhost:8080/api/exemples) — insertion d’une ligne dans la table `exemple`. Corps JSON `{"libelle":"…"}` (`libelle` optionnel, max 255 caractères). Réponses : **201** `{"id", "libelle"}` ; **400** JSON invalide ou `libelle` trop long ; **405** si ce n’est pas `POST` ; **500** erreur SQL.

  Après le tout premier `docker compose up`, attends quelques secondes que MySQL ait fini d’exécuter `init.sql` avant le premier `POST`.

  ```bash
  curl -X POST http://localhost:8080/api/exemples \
    -H 'Content-Type: application/json' \
    -d '{"libelle":"Ma première ligne"}'
  ```

- **phpMyAdmin** : [http://localhost:8081](http://localhost:8081) — hôte **db**, port **3306**, identifiants cohérents avec `docker-compose.yml` (root / mot de passe root dans l’exemple fourni).

Arrêter les conteneurs :

```bash
docker compose down
```

Les données MySQL restent dans le dossier **`db-data/`** sur ta machine (montage bind). Ce dossier n’est pas supprimé par `docker compose down` seul.

---

## Comment le schéma SQL est « injecté » au lancement (important)

Le [guide](../README.md) évoque qu’un script SQL peut être monté dans le conteneur, mais pas toujours le **mécanisme exact**. Voici ce qui se passe ici.

1. L’image officielle **MySQL** exécute, **une seule fois**, tout script placé dans `/docker-entrypoint-initdb.d/` **lors de la toute première initialisation** du répertoire de données (quand le répertoire de données du volume est vide).
2. Dans `docker-compose.yml`, la ligne suivante **monte** ton fichier local dans ce dossier :

   ```yaml
   - ./database/init.sql:/docker-entrypoint-initdb.d/01-init.sql:ro
   ```

3. Donc : **premier** `docker compose up` avec **`db-data` vide** → MySQL initialise le stockage et exécute `01-init.sql`.  
4. Si **`db-data/` contient déjà** des fichiers de données, MySQL **ne relance pas** ces scripts : modifier `database/init.sql` puis `docker compose up` **ne met pas à jour** automatiquement les tables existantes.

C’est pour cela que beaucoup de débutants croient que « le SQL ne marche pas » : en réalité, la base a déjà été initialisée une première fois.

---

## Repartir de zéro (base cassée ou schéma à refaire)

Si tu veux **tout réinitialiser** (tu perds les données en base) :

```bash
cd boilerplate-projet-minimal
docker compose down
rm -rf db-data
docker compose up -d --build
```

Comme `db-data` est un **dossier monté** sur l’hôte, `docker compose down -v` ne suffit pas forcément à effacer ces fichiers : supprimer **`db-data/`** garantit que MySQL repartira de zéro et **réexécutera** les scripts dans `/docker-entrypoint-initdb.d/`, dont `database/init.sql`.

---

## Structure des dossiers (résumé)

```
boilerplate-projet-minimal/
├── docker-compose.yml
├── .env.example
├── README.md
├── database/
│   └── init.sql
├── db-data/                 # créé au premier run (données MySQL), listé dans `.gitignore`
├── backend/
│   ├── Dockerfile
│   ├── apache.conf
│   ├── public/
│   │   └── api/
│   │       ├── .htaccess      # URLs sans .php : healthcheck, fake, exemples
│   │       ├── db.php
│   │       ├── healthcheck.php
│   │       ├── fake.php
│   │       └── exemples.php
└── frontend/
    ├── index.html
    ├── css/styles.css
    └── js/app.js
```

Les routes `/api/healthcheck`, `/api/fake` et `/api/exemples` sont réécrites vers les fichiers PHP par **mod_rewrite** (`.htaccess`).

---

## Prochaines étapes pour ton vrai projet

1. Adapter le contenu de **`database/init.sql`** avec la syntaxe **MySQL** (types, auto-incrément, etc.) et utiliser `down` + suppression de `db-data` une fois si besoin pour repartir propre.
2. Étendre l’API et la couche **PDO** (autres verbes HTTP, lectures, règles métier) en réutilisant **`db.php`** ou une couche dédiée.
3. Enrichir le front (pages, `fetch` vers tes vrais endpoints, y compris `POST /api/exemples`).
4. Versionner avec **Git** ; ne pas commiter `.env`.

Pour la méthodologie (checklist, évaluation, sécurité), reste sur le **[guide principal](../README.md)** et sur l’exemple plus complet **[film-library](../film-library/)**.
