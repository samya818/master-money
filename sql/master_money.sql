-- ═══════════════════════════════════════
-- MASTER MONEY — Base de données complète
-- ═══════════════════════════════════════

CREATE DATABASE IF NOT EXISTS `master_money` CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `master_money`;

-- ── Utilisateurs ──
CREATE TABLE IF NOT EXISTS `utilisateurs` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    avatar VARCHAR(50) DEFAULT 'default',
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── Budgets mensuels ──
CREATE TABLE IF NOT EXISTS `budgets` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    mois VARCHAR(7) NOT NULL COMMENT 'Format: 2026-03',
    bourse DECIMAL(10,2) DEFAULT 0,
    aide_familiale DECIMAL(10,2) DEFAULT 0,
    emploi DECIMAL(10,2) DEFAULT 0,
    loyer DECIMAL(10,2) DEFAULT 0,
    transport DECIMAL(10,2) DEFAULT 0,
    alimentation DECIMAL(10,2) DEFAULT 0,
    loisirs DECIMAL(10,2) DEFAULT 0,
    imprevus DECIMAL(10,2) DEFAULT 0,
    reste_a_vivre DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── Dépenses quotidiennes ──
CREATE TABLE IF NOT EXISTS `depenses` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    categorie ENUM('logement','nourriture','transport','loisirs','factures','autre') NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    description VARCHAR(200),
    date_depense DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── Objectifs d'épargne ──
CREATE TABLE IF NOT EXISTS `objectifs` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    montant_cible DECIMAL(10,2) NOT NULL,
    montant_actuel DECIMAL(10,2) DEFAULT 0,
    duree_mois INT NOT NULL,
    date_debut DATE NOT NULL,
    statut ENUM('en_cours','atteint','abandonne') DEFAULT 'en_cours',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── Badges / Gamification ──
CREATE TABLE IF NOT EXISTS `badges` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icone VARCHAR(10) NOT NULL,
    points_requis INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `utilisateur_badges` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    badge_id INT NOT NULL,
    obtenu_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── Données badges par défaut ──
INSERT IGNORE INTO `badges` (code, nom, description, icone, points_requis) VALUES
('premier_budget',  'Premier Budget',       'Vous avez créé votre premier budget !',           '🎯', 0),
('economiseur',     'Économiseur',          'Vous avez épargné ce mois-ci.',                   '💰', 10),
('bon_gestionnaire','Bon Gestionnaire',     '3 mois consécutifs avec un solde positif.',       '📊', 30),
('objectif_atteint','Objectif Atteint',     'Vous avez atteint un objectif d\'épargne !',      '🏆', 50),
('regulier',        'Régulier',             'Connecté 7 jours consécutifs.',                   '🔥', 20),
('budget_master',   'Budget Master',        '6 mois avec un budget équilibré.',                '👑', 100);

-- ── Moyennes étudiantes (comparaison) ──
CREATE TABLE IF NOT EXISTS `moyennes_etudiants` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie VARCHAR(50) NOT NULL,
    montant_moyen DECIMAL(10,2) NOT NULL,
    annee INT NOT NULL,
    mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `moyennes_etudiants` (categorie, montant_moyen, annee) VALUES
('loyer',        1200.00, 2026),
('alimentation',  600.00, 2026),
('transport',     250.00, 2026),
('loisirs',       150.00, 2026),
('imprevus',      100.00, 2026);