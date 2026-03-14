# Plan de migration — Master Money → Vercel + Railway + Supabase

## Objectif
Déployer l’application PHP/MySQL existante sur **Vercel (frontend statique)** + **Railway (backend PHP)** + **Supabase (PostgreSQL)** en conservant au maximum le code et la logique métier.

---

## 1. Vue d’ensemble de l’architecture cible

| Composant | Rôle | Technologie |
|-----------|------|-------------|
| **Vercel** | Servir HTML, CSS, JS statiques ; pas d’exécution PHP | Fichiers statiques |
| **Railway** | Exécuter le backend PHP (API JSON) | PHP 8 + serveur intégré ou Nginx/PHP-FPM |
| **Supabase** | Base de données | PostgreSQL |
| **Auth** | Remplacer les sessions par JWT (stocké en localStorage côté client) | JWT vérifié à chaque requête API |

Le frontend (Vercel) appelle l’API (Railway) en AJAX avec le header `Authorization: Bearer <token>`. L’API lit le token, vérifie la signature et charge l’utilisateur.

---

## 2. Étapes détaillées

### Phase A — Base de données et configuration

| Étape | Fichier(s) | Action |
|-------|------------|--------|
| A1 | `config/db.php` | Remplacer MySQL par PDO PostgreSQL ; utiliser variables d’environnement (`DATABASE_URL` ou `PG_HOST`, `PG_DATABASE`, etc.). |
| A2 | `sql/master_money_pg.sql` | Schéma PostgreSQL : types `SERIAL`, pas de `ENGINE`, `ENUM` remplacés par `VARCHAR` + `CHECK`, `INSERT IGNORE` → `ON CONFLICT DO NOTHING`, `SHOW COLUMNS` supprimé (colonnes définies dans le schéma). |
| A3 | Variables d’environnement | Sur Railway : `DATABASE_URL` (format Supabase) ou `PG_HOST`, `PG_USER`, `PG_PASSWORD`, `PG_DATABASE`. Sur Supabase : créer le projet et exécuter le script SQL. |

### Phase B — Authentification JWT

| Étape | Fichier(s) | Action |
|-------|------------|--------|
| B1 | `lib/jwt.php` | Fonctions `jwt_encode($payload)`, `jwt_decode($token)` avec clé secrète lue depuis `getenv('JWT_SECRET')`. |
| B2 | `api/login.php` | POST `email` + `mot_de_passe` → vérification en BDD, si OK renvoie `{ "token": "...", "user": { id, nom, email, avatar, points } }`. |
| B3 | `api/register.php` | POST `nom`, `email`, `mot_de_passe`, `confirmation` → validation, insertion utilisateur, renvoie token + user (comme login). |
| B4 | `api/auth.php` | Middleware : lit `Authorization: Bearer <token>`, décode le JWT, vérifie expiration et signature, définit `$current_user_id` (et éventuellement `$current_user`) pour les scripts inclus. Renvoie 401 JSON si invalide. |

Aucune colonne “token” en base n’est nécessaire : le JWT est stateless (payload = user_id + exp).

### Phase C — Endpoints API (backend Railway)

Chaque endpoint protégé inclut `api/auth.php` puis exécute la logique métier existante et renvoie du JSON.

| Endpoint | Méthode | Rôle | Fichier source logique |
|----------|---------|------|-------------------------|
| `/api/login` | POST | Connexion → token + user | `connexion.php` |
| `/api/register` | POST | Inscription → token + user | `inscription.php` |
| `/api/dashboard` | GET | Dernier budget, historique 6 mois, points, badges, moyennes, recommandations | `tableau-de-bord.php` |
| `/api/budget` | POST | Créer un budget (même calcul que `calculateur.php`) | `calculateur.php` |
| `/api/budget` | GET | Liste des budgets (ex. 10 derniers) | `tableau-de-bord.php` |
| `/api/expenses` | GET | Liste dépenses (filtre période : jour/semaine/mois) + stats par catégorie | `depenses.php` |
| `/api/expenses` | POST | Ajouter une dépense | `depenses.php` |
| `/api/expenses/:id` | DELETE | Supprimer une dépense | `depenses.php` |
| `/api/objectifs` | GET | Liste objectifs | `objectifs.php` |
| `/api/objectifs` | POST | Créer objectif ou “épargner” (selon body) | `objectifs.php` |
| `/api/objectifs/:id` | DELETE | Supprimer objectif | `objectifs.php` |
| `/api/simulation` | POST | Corps : revenus, dépenses, épargne, durée → projection (pas d’écriture BDD) | `simulation.php` |
| `/api/comparaison` | GET | Dernier budget + moyennes UMI + moyennes plateforme | `comparaison.php` |
| `/api/profile` | GET | Profil utilisateur + stats + badges | `profil.php` |
| `/api/profile` | PATCH/POST | Mise à jour nom, mot de passe, avatar (optionnel) | `profil.php` |
| `/api/export-data` | GET | Données pour génération PDF/CSV côté client | `export-pdf.php` |

Fichiers à créer :
- `api/login.php`, `api/register.php`, `api/auth.php`
- `api/dashboard.php`, `api/budget.php`, `api/expenses.php`, `api/objectifs.php`, `api/simulation.php`, `api/comparaison.php`, `api/profile.php`, `api/export-data.php`
- `public/index.php` (routeur qui envoie `/api/*` vers les bons fichiers).

### Phase D — Adaptations SQL MySQL → PostgreSQL

À appliquer dans tous les fichiers PHP qui accèdent à la BDD :

| MySQL | PostgreSQL |
|-------|------------|
| `SHOW COLUMNS FROM budgets` | Supprimer ou utiliser `information_schema.columns` ; ici on suppose que le schéma est fixe (colonnes `mois`, etc. présentes). |
| `DATE_FORMAT(created_at,'%Y-%m')` | `to_char(created_at, 'YYYY-MM')` |
| `DATE_SUB(CURDATE(), INTERVAL 7 DAY)` | `CURRENT_DATE - INTERVAL '7 days'` |
| `CURDATE()` | `CURRENT_DATE` |
| `MONTH(date_depense)=MONTH(CURDATE())` | `EXTRACT(MONTH FROM date_depense)=EXTRACT(MONTH FROM CURRENT_DATE)` |
| `YEAR(date_depense)=YEAR(CURDATE())` | `EXTRACT(YEAR FROM date_depense)=EXTRACT(YEAR FROM CURRENT_DATE)` |
| `INSERT IGNORE` | `INSERT ... ON CONFLICT (...) DO NOTHING` |
| `AUTO_INCREMENT` | `SERIAL` / `GENERATED BY DEFAULT AS IDENTITY` |
| Colonnes `ENUM` | Déjà remplacées par `VARCHAR` + `CHECK` dans le schéma PG. |

Fichiers concernés : `tableau-de-bord.php` (historique, SHOW COLUMNS), `depenses.php` (filtres date), `export-pdf.php` (MONTH/YEAR), `comparaison.php` (requêtes agrégées), etc. Toute cette logique sera reprise dans les fichiers `api/*.php` avec la syntaxe PostgreSQL.

### Phase E — Frontend statique (Vercel)

| Fichier actuel | Fichier cible | Comportement |
|----------------|---------------|--------------|
| `connexion.php` | `connexion.html` + `js/auth.js` | Formulaire → `fetch(POST /api/login)` → stocker token (localStorage) → redirection vers tableau de bord. |
| `inscription.php` | `inscription.html` + même `js/auth.js` | Idem avec `/api/register`. |
| `tableau-de-bord.php` | `tableau-de-bord.html` + `js/dashboard.js` | Au chargement : `fetch(GET /api/dashboard)` avec token → affichage des données (KPIs, graphiques Chart.js, etc.). |
| `calculateur.php` | `calculateur.html` + `js/calculateur.js` | Formulaire → `fetch(POST /api/budget)` → affichage résultat + lien dashboard. |
| `depenses.php` | `depenses.html` + `js/depenses.js` | GET `/api/expenses?periode=mois`, POST pour ajout, DELETE pour suppression. |
| `objectifs.php` | `objectifs.html` + `js/objectifs.js` | GET/POST/DELETE `/api/objectifs`. |
| `simulation.php` | `simulation.html` + `js/simulation.js` | POST `/api/simulation` avec paramètres → affichage tableau + graphique. |
| `comparaison.php` | `comparaison.html` + `js/comparaison.js` | GET `/api/comparaison`. |
| `guide.php` | `guide.html` | Contenu statique (éventuellement chargé depuis un JSON ou inclus en dur). |
| `profil.php` | `profil.html` + `js/profil.js` | GET/PATCH `/api/profile`, formulaire avatar (upload vers API ou Supabase Storage). |
| `export-pdf.php` | `export-pdf.html` + `js/export-pdf.js` | GET `/api/export-data` → génération PDF/CSV côté client (jsPDF + html2canvas / CSV). |
| `includes/header.php` | Fragment réutilisable en JS ou dupliqué dans chaque HTML | Navbar : si token absent → Connexion/Inscription ; si token présent → menu complet + déconnexion (suppression token + redirection). |
| `index.html` | Inchangé (landing) | Liens vers connexion/inscription. |

Variable globale frontend : `window.API_BASE_URL` (ex. `https://votre-app.railway.app`) définie dans un `config.js` ou dans chaque page, pour que tous les `fetch` pointent vers Railway.

### Phase F — Configuration Railway

| Élément | Contenu |
|--------|--------|
| `Dockerfile` | Image PHP 8.x (cli), extension pdo_pgsql, copie du code, `EXPOSE $PORT`, `CMD ["php", "-S", "0.0.0.0:$PORT", "public/index.php"]` (en utilisant un routeur qui redirige `/api/*` vers les scripts correspondants). |
| `public/index.php` | Routeur : `$_SERVER['REQUEST_URI']` → si `/api/login` inclut `api/login.php`, si `/api/dashboard` inclut `api/auth.php` puis `api/dashboard.php`, etc. |
| Variables d’environnement Railway | `DATABASE_URL` (chaîne Supabase), `JWT_SECRET` (clé forte), éventuellement `PORT` fourni par Railway. |

Note : pour servir l’API à la racine du domaine Railway, le routeur peut réécrire `/api/xxx` en incluant `api/xxx.php`. Si Railway expose uniquement le port, la base URL côté Vercel sera `https://votre-projet.railway.app`.

### Phase G — Configuration Vercel

| Fichier | Rôle |
|---------|------|
| `vercel.json` | `builds` pour fichiers statiques (ou pas de build), `routes` pour servir `index.html`, `connexion.html`, etc. ; pas de rewrite vers Railway pour les pages (les appels API sont faits en JS vers l’URL Railway). |
| CORS | Sur Railway, dans le routeur ou dans chaque `api/*.php`, envoyer `Access-Control-Allow-Origin: https://votre-domaine.vercel.app` (ou `*` en dev) et `Access-Control-Allow-Headers: Authorization, Content-Type` pour que le navigateur autorise les requêtes depuis Vercel. |

---

## 3. Fichiers à créer ou modifier (résumé)

### Nouveaux fichiers
- `config/db.php` (remplacer contenu actuel par PostgreSQL + env).
- `sql/master_money_pg.sql` (schéma complet PostgreSQL).
- `lib/jwt.php` (encode/decode JWT).
- `api/auth.php`, `api/login.php`, `api/register.php`.
- `api/dashboard.php`, `api/budget.php`, `api/expenses.php`, `api/objectifs.php`, `api/simulation.php`, `api/comparaison.php`, `api/profile.php`, `api/export-data.php`.
- `public/index.php` (routeur API + en-têtes CORS).
- `Dockerfile` (pour Railway).
- `vercel.json` (pour le frontend).
- Pages HTML statiques + JS (connexion, inscription, tableau-de-bord, calculateur, depenses, objectifs, simulation, comparaison, guide, profil, export-pdf).
- `js/config.js` (API_BASE_URL), `js/auth.js` (token, redirection, header Authorization).

### Fichiers existants à conserver tels quels (optionnel)
- Les anciens `.php` peuvent rester dans le dépôt pour référence ou pour un déploiement “classique” ailleurs ; ils ne sont pas utilisés par Vercel (statique) ni par le routeur Railway (seuls les `api/*.php` le sont).

---

## 4. Ordre d’exécution recommandé

1. Créer le schéma PostgreSQL et le déployer sur Supabase.
2. Mettre en place `config/db.php` et tester la connexion depuis un script local (variables d’environnement).
3. Implémenter JWT et `api/login.php`, `api/register.php`, `api/auth.php` ; tester avec Postman/curl.
4. Implémenter les endpoints API un par un en reprenant la logique des PHP existants et en adaptant le SQL à PostgreSQL.
5. Créer le routeur `public/index.php` et le Dockerfile ; déployer sur Railway et vérifier CORS.
6. Créer les pages HTML/JS du frontend et définir `API_BASE_URL` vers Railway ; déployer sur Vercel.
7. Tester le flux complet : inscription → connexion → dashboard → budget → dépenses → objectifs → comparaison → profil → déconnexion.

---

## 5. Notes importantes

- **Avatar** : aujourd’hui stocké en local dans `images/avatars/`. Sur Railway (filesystem éphémère), il faut soit utiliser **Supabase Storage** (upload depuis le frontend ou depuis l’API PHP via le client Supabase), soit ne pas persister l’avatar en premier déploiement. On peut exposer `PATCH /api/profile` avec un champ `avatar_url` (URL Supabase Storage) et laisser le client uploader l’image vers Supabase puis envoyer l’URL.
- **Guide** : contenu statique ; peut rester en HTML pur sur Vercel sans appel API.
- **Export PDF** : la logique reste côté client (jsPDF + html2canvas) ; l’API fournit uniquement les données (`/api/export-data`).

Ce plan permet de migrer progressivement tout en gardant la logique métier d’origine dans le backend PHP.

---

## 6. Livrables fournis (résumé)

| Élément | Fichier(s) |
|--------|------------|
| **Plan** | `MIGRATION_PLAN.md` |
| **Config BDD** | `config/db.php` (PostgreSQL + variables d’environnement) |
| **Schéma PostgreSQL** | `sql/master_money_pg.sql` |
| **JWT** | `lib/jwt.php` |
| **Auth API** | `api/auth.php`, `api/login.php`, `api/register.php` |
| **Endpoints API** | `api/dashboard.php`, `api/budget.php`, `api/expenses.php`, `api/objectifs.php`, `api/simulation.php`, `api/comparaison.php`, `api/profile.php`, `api/export_data.php` |
| **Routeur + CORS** | `public/index.php` |
| **Railway** | `Dockerfile` |
| **Vercel** | `vercel.json` |
| **Frontend auth** | `js/config.js`, `js/auth.js`, `connexion.html`, `inscription.html` (à la racine) |
| **Déploiement** | `DEPLOY.md` |

Les autres pages (tableau-de-bord, calculateur, depenses, objectifs, simulation, comparaison, guide, profil, export-pdf) sont à créer en HTML + JS en réutilisant le HTML des fichiers PHP existants et en appelant les endpoints avec `auth.fetch('/api/...')`. Le pattern est le même que pour `connexion.html` / `inscription.html` : charger `config.js` et `auth.js`, vérifier `auth.requireAuth()` pour les pages protégées, puis `auth.fetch('/api/dashboard')` etc. et afficher les données.
