# Déploiement Master Money — Vercel + Railway + Supabase

## Prérequis
- Compte [Supabase](https://supabase.com), [Railway](https://railway.app), [Vercel](https://vercel.com)
- Dépot Git (GitHub/GitLab) pour connecter les services

---

## 1. Supabase (Base de données)

1. Créer un projet Supabase.
2. Aller dans **SQL Editor** et exécuter le contenu de `sql/master_money_pg.sql`.
3. Dans **Settings → Database**, récupérer l’**Connection string** (URI PostgreSQL). Exemple :  
   `postgresql://postgres.[ref]:[password]@aws-0-[region].pooler.supabase.com:6543/postgres?sslmode=require`

---

## 2. Railway (Backend PHP)

1. Créer un nouveau projet Railway, **Deploy from GitHub** (ou **Deploy from repo**).
2. Choisir le dépôt et la racine du projet.
3. **Settings → Build** :  
   - **Builder** : Dockerfile  
   - **Dockerfile path** : `Dockerfile` (à la racine)
4. **Variables d’environnement** :
   - `DATABASE_URL` = l’URI Supabase (étape 1)
   - `JWT_SECRET` = une chaîne aléatoire forte (ex. `openssl rand -base64 32`)
   - Optionnel : `CORS_ORIGIN` = l’URL du frontend Vercel (ex. `https://master-money.vercel.app`) pour restreindre CORS
5. Déployer. Noter l’URL publique du service (ex. `https://master-money-production-xxxx.up.railway.app`).

---

## 3. Vercel (Frontend statique)

1. Importer le projet depuis Git (même dépôt).
2. **Framework Preset** : Other (pas de build).
3. **Root Directory** : la racine du projet (où se trouvent `index.html`, `connexion.html`, `vercel.json`, `css/`, `js/`).
4. **Variables d’environnement** (optionnel) :  
   - Créer une variable `NEXT_PUBLIC_API_URL` ou utiliser une autre convention. Pour injecter l’URL de l’API dans le frontend, il faut soit :
   - une valeur build-time (si vous ajoutez un petit build qui remplace `API_BASE_URL` dans `js/config.js`),  
   - soit définir `window.API_BASE_URL` dans chaque page après chargement de `config.js` (ex. dans un script inline qui lit une variable d’environnement Vercel — Vercel n’expose pas les env vars au runtime client, donc en pratique on met l’URL Railway en dur dans `js/config.js` ou on fait un build qui l’injecte).
5. **Important** : Dans `js/config.js`, remplacer `https://votre-app.railway.app` par l’URL réelle de votre backend Railway (ou configurer un build step qui le fait).
6. Déployer. Les rewrites dans `vercel.json` servent les pages (connexion, inscription, etc.).

---

## 4. Résumé des URLs

| Service   | URL type |
|----------|----------|
| Frontend | `https://votre-projet.vercel.app` |
| Backend  | `https://votre-projet.railway.app` |
| Supabase | Connection string dans le dashboard |

Dans `js/config.js` :  
`window.API_BASE_URL = 'https://votre-projet.railway.app';`

---

## 5. Vérifications

- **Connexion** : ouvrir la page de connexion sur Vercel, s’inscrire puis se connecter. Vérifier que le token est bien stocké (localStorage) et que les appels vers `/api/dashboard` renvoient 200.
- **CORS** : si les requêtes depuis Vercel vers Railway sont bloquées, vérifier que Railway envoie bien `Access-Control-Allow-Origin` (déjà géré dans `public/index.php`). En production, définir `CORS_ORIGIN` sur l’URL exacte du frontend.
- **Base de données** : en cas d’erreur 500 sur les endpoints, vérifier les logs Railway et que `DATABASE_URL` est correct (format Supabase avec `?sslmode=require` si nécessaire).

---

## 6. Structure des dossiers pour le déploiement

- **Vercel** sert tout ce qui est à la racine : `index.html`, `connexion.html`, `inscription.html`, `css/`, `js/`, `images/`, etc. Les pages protégées (tableau-de-bord, calculateur, etc.) peuvent être ajoutées en HTML + JS qui appellent l’API avec `auth.fetch()`.
- **Railway** exécute le contenu du **Dockerfile** : il ne déploie que ce qui est copié (config, lib, api, public). Les fichiers PHP à la racine (anciens `connexion.php`, `tableau-de-bord.php`, etc.) ne sont pas utilisés par le routeur ; seuls les scripts dans `api/` le sont.

Pour déployer uniquement le backend sur Railway à partir d’un monorepo, vous pouvez définir le **Root Directory** Railway sur un sous-dossier qui contient `Dockerfile`, `config/`, `lib/`, `api/`, `public/` (et éventuellement `sql/`). Sinon, à la racine, le Dockerfile actuel copie bien ces dossiers.
