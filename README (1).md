# 💰 Master Money

> Application web de gestion budgétaire pour étudiants marocains — développée avec PHP, MySQL et XAMPP.

---

## 📋 Table des matières

- [Présentation](#présentation)
- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Structure du projet](#structure-du-projet)
- [Installation](#installation)
- [Configuration](#configuration)
- [Base de données](#base-de-données)
- [Pages et routes](#pages-et-routes)
- [Auteur](#auteur)

---

## 🎯 Présentation

**Master Money** est une application web conçue pour aider les étudiants marocains à gérer leur budget mensuel. Elle permet de suivre les revenus (bourse, aide familiale, emploi étudiant), les dépenses par catégorie, les objectifs d'épargne, et de se comparer aux moyennes étudiantes nationales.

---

## ✨ Fonctionnalités

| Fonctionnalité | Description |
|---|---|
| 🔐 Authentification | Inscription, connexion, déconnexion sécurisée |
| 📊 Dashboard | Vue d'ensemble du budget mensuel avec graphiques |
| 🧮 Calculateur | Saisie des revenus et dépenses, calcul du reste à vivre |
| 💸 Dépenses | Suivi des dépenses quotidiennes par catégorie |
| 🎯 Objectifs | Création et suivi d'objectifs d'épargne |
| 📈 Simulation | Simulation budgétaire sur plusieurs mois |
| 👥 Comparaison | Comparaison avec les moyennes étudiantes |
| ⭐ Bons Plans | Astuces et bons plans pour économiser |
| 📚 Guide | Guide financier pour étudiants |
| 🏆 Badges | Système de gamification avec points et badges |
| 🌙 Thème | Mode sombre / mode clair |
| 📄 Export PDF | Export du budget en PDF |

---

## 🛠 Stack technique

- **Backend** : PHP 8.x
- **Base de données** : MySQL via PDO
- **Serveur local** : XAMPP (Apache + MySQL)
- **Frontend** : HTML5, CSS3, JavaScript vanilla
- **Graphiques** : Chart.js
- **Fonts** : Syne + DM Sans (Google Fonts)

---

## 📁 Structure du projet

```
master-money/
│
├── config/
│   └── db.php                  # Connexion PDO à la base de données
│
├── css/
│   └── style.css               # Styles globaux
│
├── images/
│   ├── avatars/                # Photos de profil utilisateurs
│   ├── favicon.png
│   └── logo.png
│
├── includes/
│   ├── header.php              # Navbar + variables CSS + thème
│   └── footer.php              # Pied de page + scripts
│
├── js/
│   └── calculateur.js          # Scripts du calculateur
│
├── sql/
│   └── master_money.sql        # Script SQL de création de la BDD
│
├── calculateur.php             # Calculateur de budget mensuel
├── comparaison.php             # Comparaison avec moyennes étudiantes
├── connexion.php               # Page de connexion
├── deconnexion.php             # Déconnexion + destruction session
├── depenses.php                # Gestion des dépenses quotidiennes
├── export-pdf.php              # Export du budget en PDF
├── guide.php                   # Guide financier
├── index.html                  # Page d'accueil publique
├── inscription.php             # Création de compte
├── objectifs.php               # Objectifs d'épargne
├── profil.php                  # Profil utilisateur
├── simulation.php              # Simulation budgétaire
└── tableau-de-bord.php         # Dashboard principal
```

---

## 🚀 Installation

### Prérequis

- [XAMPP](https://www.apachefriends.org/) installé (Apache + MySQL + PHP 8+)
- Un navigateur web moderne

### Étapes

**1. Copier le projet**
```
Copiez le dossier master-money dans :
C:\xampp\htdocs\master-money\
```

**2. Démarrer XAMPP**
- Ouvrez le panneau de contrôle XAMPP
- Démarrez **Apache** et **MySQL**

**3. Créer la base de données**
- Allez sur `http://localhost/phpmyadmin`
- Créez une base nommée `master_money`
- Importez le fichier `sql/master_money.sql`

**4. Accéder à l'application**
```
http://localhost/master-money/
```

---

## ⚙️ Configuration

### `config/db.php`

```php
<?php
$host = "127.0.0.1";
$user = "root";
$pass = "VOTRE_MOT_DE_PASSE";

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=master_money;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur BDD : " . $e->getMessage());
}
?>
```

> ⚠️ Utilisez `127.0.0.1` plutôt que `localhost` pour éviter les conflits de socket MySQL sous XAMPP.

---

## 🗄️ Base de données

### Tables principales

| Table | Description |
|---|---|
| `utilisateurs` | Comptes utilisateurs (nom, email, mot de passe hashé, points) |
| `budgets` | Budgets mensuels (revenus, dépenses, reste à vivre) |
| `depenses` | Dépenses quotidiennes par catégorie |
| `objectifs` | Objectifs d'épargne avec suivi de progression |
| `badges` | Badges disponibles dans le système de gamification |
| `utilisateur_badges` | Badges obtenus par chaque utilisateur |
| `moyennes_etudiants` | Données de référence pour la comparaison |

### Schéma `budgets`

```sql
CREATE TABLE budgets (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    mois            VARCHAR(7) NOT NULL DEFAULT '',
    bourse          DECIMAL(10,2) DEFAULT 0,
    aide_familiale  DECIMAL(10,2) DEFAULT 0,
    emploi          DECIMAL(10,2) DEFAULT 0,
    loyer           DECIMAL(10,2) DEFAULT 0,
    transport       DECIMAL(10,2) DEFAULT 0,
    alimentation    DECIMAL(10,2) DEFAULT 0,
    loisirs         DECIMAL(10,2) DEFAULT 0,
    imprevus        DECIMAL(10,2) DEFAULT 0,
    reste_a_vivre   DECIMAL(10,2),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);
```

---

## 🔗 Pages et routes

| URL | Fichier | Accès |
|---|---|---|
| `/master-money/` | `index.html` | Public |
| `/master-money/inscription.php` | `inscription.php` | Public |
| `/master-money/connexion.php` | `connexion.php` | Public |
| `/master-money/tableau-de-bord.php` | `tableau-de-bord.php` | Connecté |
| `/master-money/calculateur.php` | `calculateur.php` | Connecté |
| `/master-money/depenses.php` | `depenses.php` | Connecté |
| `/master-money/objectifs.php` | `objectifs.php` | Connecté |
| `/master-money/simulation.php` | `simulation.php` | Connecté |
| `/master-money/comparaison.php` | `comparaison.php` | Connecté |
| `/master-money/profil.php` | `profil.php` | Connecté |
| `/master-money/guide.php` | `guide.php` | Public |
| `/master-money/bons-plans.php` | `bons-plans.php` | Public |
| `/master-money/export-pdf.php` | `export-pdf.php` | Connecté |
| `/master-money/deconnexion.php` | `deconnexion.php` | Connecté |

---

## 👤 Auteur

Projet développé dans le cadre d'un projet étudiant — Master Money © 2026
