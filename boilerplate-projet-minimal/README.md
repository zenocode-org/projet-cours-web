# Gabarit minimal de projet (PHP + front statique + Docker)

Ce dossier est un **point de départ** pour ton projet du module : structure légère, deux routes API factices, base PostgreSQL, Apache en conteneur, et une page HTML avec **Tailwind** (via CDN). Tu peux le **dupliquer** ou t’en inspirer pour créer ton propre dépôt.

> **Ce que le [guide officiel](../README.md) ne détaille pas toujours** : *comment* le fichier SQL est exécuté automatiquement au premier lancement, et *comment repartir de zéro* si la base est dans un état incohérent. C’est décrit plus bas — lis cette section avant de te demander pourquoi ton `schema.sql` « ne s’applique pas ».

---

## Contenu du gabarit

| Élément | Rôle |
|--------|------|
| `frontend/` | HTML, CSS, JS (vanilla). Tailwind chargé par CDN dans `index.html`. |
| `backend/public/api/` | API PHP : `GET /api/healthcheck` et `GET /api/fake` → réponse `{}` (JSON vide). |
| `backend/sql/schema.sql` | Schéma **minimal** (une table `exemple`) — à remplacer par ton modèle. |
| `backend/Dockerfile` | Image PHP 8.2 + Apache + extensions **PDO PostgreSQL**. |
| `backend/apache.conf` | Racine web = front ; API servie sous `/api/`. |
| `docker-compose.yml` | Services **php**, **postgres**, **adminer** (interface web pour la BDD). |

### Pourquoi Adminer et pas phpMyAdmin ?

**phpMyAdmin** est pensé pour **MySQL / MariaDB**. Ce gabarit utilise **PostgreSQL** (comme l’exemple `film-library` du dépôt). **Adminer** est une interface web **très simple** qui sait se connecter à PostgreSQL : tu ouvres `http://localhost:8081`, tu choisis « PostgreSQL », serveur `postgres`, utilisateur et mot de passe comme dans ton `.env`.

Si ton enseignant ou un tutoriel insiste sur phpMyAdmin, tu peux plus tard adapter le projet vers MariaDB + phpMyAdmin ; le guide du cours accepte **MySQL ou PostgreSQL**.

---

## Démarrage rapide

Prérequis : **Docker** et **Docker Compose** installés.

```bash
cd boilerplate-projet-minimal
cp .env.example .env
# Édite .env si tu veux changer nom d’utilisateur / mot de passe / base
docker compose up -d --build
```

Ensuite :

- **Site** : [http://localhost:8080](http://localhost:8080) (page d’accueil + bouton de test `fetch`).
- **API** : [http://localhost:8080/api/healthcheck](http://localhost:8080/api/healthcheck) et [http://localhost:8080/api/fake](http://localhost:8080/api/fake) (toutes deux en `GET`, corps JSON `{}`).
- **Adminer** : [http://localhost:8081](http://localhost:8081) — système **PostgreSQL**, serveur **`postgres`**, utilisateur / mot de passe / base : voir ton `.env` (`DB_USER`, `DB_PASSWORD`, `DB_NAME`).

Arrêter les conteneurs (sans effacer les données de la base par défaut) :

```bash
docker compose down
```

---

## Comment le schéma SQL est « injecté » au lancement (important)

Le [guide](../README.md) évoque qu’un `schema.sql` peut être monté dans le conteneur, mais pas toujours le **mécanisme exact**. Voici ce qui se passe ici.

1. L’image officielle **PostgreSQL** exécute, **une seule fois**, tout script placé dans `/docker-entrypoint-initdb.d/` **lors de la toute première initialisation** du répertoire de données (quand le volume est vide).
2. Dans `docker-compose.yml`, la ligne suivante **monte** ton fichier local dans ce dossier magique :

   ```yaml
   - ./backend/sql/schema.sql:/docker-entrypoint-initdb.d/01-schema.sql
   ```

3. Donc : **premier** `docker compose up` avec un volume **neuf** → PostgreSQL crée la base et exécute `01-schema.sql`.  
4. Si le volume **`postgres_data` existe déjà** avec des données, PostgreSQL **ne relance pas** ces scripts : modifier `schema.sql` puis `docker compose up` **ne met pas à jour** automatiquement les tables existantes.

C’est pour cela que beaucoup de débutants croient que « le SQL ne marche pas » : en réalité, la base a déjà été initialisée une première fois.

---

## Repartir de zéro (base cassée ou schéma à refaire)

Si tu veux **tout réinitialiser** (tu perds les données en base) :

```bash
cd boilerplate-projet-minimal
docker compose down -v
```

L’option **`-v`** supprime les **volumes nommés** déclarés dans le compose (ici `postgres_data`), donc le prochain `docker compose up -d` recrée un volume vide et PostgreSQL **réexécute** les scripts dans `/docker-entrypoint-initdb.d/`, dont ton `schema.sql`.

Puis :

```bash
docker compose up -d --build
```

**Sans** `-v`, `docker compose down` garde le volume : tes données restent, mais ton schéma modifié ne sera pas rejoué automatiquement.

---

## Structure des dossiers (résumé)

```
boilerplate-projet-minimal/
├── docker-compose.yml
├── .env.example
├── README.md
├── backend/
│   ├── Dockerfile
│   ├── apache.conf
│   ├── public/
│   │   └── api/
│   │       ├── .htaccess      # URLs sans .php pour healthcheck / fake
│   │       ├── healthcheck.php
│   │       └── fake.php
│   └── sql/
│       └── schema.sql
└── frontend/
    ├── index.html
    ├── css/styles.css
    └── js/app.js
```

Les routes `/api/healthcheck` et `/api/fake` sont réécrites vers les fichiers PHP par **mod_rewrite** (`.htaccess`).

---

## Prochaines étapes pour ton vrai projet

1. Remplacer le contenu de `schema.sql` par **tes** tables (et utiliser `down -v` une fois si besoin pour repartir propre).
2. Ajouter en PHP une couche **PDO** (connexion via variables d’environnement `DB_*` déjà passées par Compose).
3. Enrichir le front (pages, `fetch` vers tes vrais endpoints).
4. Versionner avec **Git** ; ne pas commiter `.env`.

Pour la méthodologie (checklist, évaluation, sécurité), reste sur le **[guide principal](../README.md)** et sur l’exemple plus complet **[film-library](../film-library/)**.
