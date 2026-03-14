-- ═══════════════════════════════════════
-- MASTER MONEY — Schéma PostgreSQL (Supabase)
-- ═══════════════════════════════════════

-- Utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default',
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Budgets mensuels
CREATE TABLE IF NOT EXISTS budgets (
    id SERIAL PRIMARY KEY,
    utilisateur_id INT NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE,
    mois VARCHAR(7) NOT NULL,
    bourse DECIMAL(10,2) DEFAULT 0,
    aide_familiale DECIMAL(10,2) DEFAULT 0,
    emploi DECIMAL(10,2) DEFAULT 0,
    loyer DECIMAL(10,2) DEFAULT 0,
    transport DECIMAL(10,2) DEFAULT 0,
    alimentation DECIMAL(10,2) DEFAULT 0,
    loisirs DECIMAL(10,2) DEFAULT 0,
    imprevus DECIMAL(10,2) DEFAULT 0,
    reste_a_vivre DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Dépenses quotidiennes (catégorie en VARCHAR + CHECK au lieu de ENUM)
CREATE TABLE IF NOT EXISTS depenses (
    id SERIAL PRIMARY KEY,
    utilisateur_id INT NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE,
    categorie VARCHAR(20) NOT NULL CHECK (categorie IN ('logement','nourriture','transport','loisirs','factures','autre')),
    montant DECIMAL(10,2) NOT NULL,
    description VARCHAR(200),
    date_depense DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Objectifs d'épargne
CREATE TABLE IF NOT EXISTS objectifs (
    id SERIAL PRIMARY KEY,
    utilisateur_id INT NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE,
    titre VARCHAR(150) NOT NULL,
    montant_cible DECIMAL(10,2) NOT NULL,
    montant_actuel DECIMAL(10,2) DEFAULT 0,
    duree_mois INT NOT NULL,
    date_debut DATE NOT NULL,
    statut VARCHAR(20) DEFAULT 'en_cours' CHECK (statut IN ('en_cours','atteint','abandonne')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Badges
CREATE TABLE IF NOT EXISTS badges (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    icone VARCHAR(10) NOT NULL,
    points_requis INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS utilisateur_badges (
    id SERIAL PRIMARY KEY,
    utilisateur_id INT NOT NULL REFERENCES utilisateurs(id) ON DELETE CASCADE,
    badge_id INT NOT NULL REFERENCES badges(id),
    obtenu_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Données badges par défaut (ON CONFLICT au lieu de INSERT IGNORE)
INSERT INTO badges (code, nom, description, icone, points_requis) VALUES
('premier_budget',  'Premier Budget',       'Vous avez créé votre premier budget !',           '🎯', 0),
('economiseur',     'Économiseur',          'Vous avez épargné ce mois-ci.',                   '💰', 10),
('bon_gestionnaire','Bon Gestionnaire',     '3 mois consécutifs avec un solde positif.',       '📊', 30),
('objectif_atteint','Objectif Atteint',     'Vous avez atteint un objectif d''épargne !',      '🏆', 50),
('regulier',        'Régulier',             'Connecté 7 jours consécutifs.',                   '🔥', 20),
('budget_master',   'Budget Master',        '6 mois avec un budget équilibré.',                '👑', 100)
ON CONFLICT (code) DO NOTHING;

-- Moyennes étudiantes
CREATE TABLE IF NOT EXISTS moyennes_etudiants (
    id SERIAL PRIMARY KEY,
    categorie VARCHAR(50) NOT NULL,
    montant_moyen DECIMAL(10,2) NOT NULL,
    annee INT NOT NULL,
    mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(categorie, annee)
);

INSERT INTO moyennes_etudiants (categorie, montant_moyen, annee) VALUES
('loyer',        1200.00, 2026),
('alimentation',  600.00, 2026),
('transport',     250.00, 2026),
('loisirs',       150.00, 2026),
('imprevus',      100.00, 2026)
ON CONFLICT (categorie, annee) DO NOTHING;

-- Index utiles
CREATE INDEX IF NOT EXISTS idx_budgets_utilisateur_id ON budgets(utilisateur_id);
CREATE INDEX IF NOT EXISTS idx_budgets_created_at ON budgets(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_depenses_utilisateur_date ON depenses(utilisateur_id, date_depense DESC);
CREATE INDEX IF NOT EXISTS idx_objectifs_utilisateur_id ON objectifs(utilisateur_id);
