-- ====================================
-- MAROC INFLATION - SCHÉMA DE BASE DE DONNÉES
-- ====================================

USE maroc_inflation;

-- ====================================
-- TABLE 1: IPC Mensuel National
-- ====================================
CREATE TABLE IF NOT EXISTS ipc_mensuel (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annee INT NOT NULL,
    mois INT NOT NULL,
    valeur_ipc DECIMAL(10, 4) NOT NULL,
    inflation_mensuelle DECIMAL(5, 2),
    inflation_annuelle DECIMAL(5, 2),
    inflation_sous_jacente DECIMAL(5, 2),
    date_publication DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_periode (annee, mois),
    INDEX idx_date (annee, mois)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE 2: Catégories d'inflation
-- ====================================
CREATE TABLE IF NOT EXISTS inflation_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annee INT NOT NULL,
    mois INT NOT NULL,
    categorie ENUM(
        'alimentation',
        'energie',
        'services',
        'produits_manufactures',
        'tabac_alcool',
        'logement',
        'transport',
        'sante',
        'education',
        'loisirs'
    ) NOT NULL,
    inflation_value DECIMAL(5, 2),
    ponderation DECIMAL(4, 2),
    FOREIGN KEY (annee, mois) REFERENCES ipc_mensuel(annee, mois) ON DELETE CASCADE,
    UNIQUE KEY unique_cat_periode (annee, mois, categorie),
    INDEX idx_categorie (categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE 3: IPC par ville
-- ====================================
CREATE TABLE IF NOT EXISTS ipc_villes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annee INT NOT NULL,
    mois INT NOT NULL,
    ville ENUM(
        'Casablanca', 'Rabat', 'Fès', 'Marrakech', 
        'Agadir', 'Tanger', 'Meknès', 'Oujda',
        'Kénitra', 'Tétouan', 'Laâyoune', 'Dakhla',
        'Guelmim', 'Settat', 'Safi', 'Beni Mellal', 'Al Hoceima'
    ) NOT NULL,
    inflation_value DECIMAL(5, 2),
    FOREIGN KEY (annee, mois) REFERENCES ipc_mensuel(annee, mois) ON DELETE CASCADE,
    UNIQUE KEY unique_ville_periode (annee, mois, ville),
    INDEX idx_ville (ville)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE 4: Métadonnées & Contexte
-- ====================================
CREATE TABLE IF NOT EXISTS metadata_inflation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annee INT NOT NULL,
    mois INT,
    evenement_contexte TEXT,
    source VARCHAR(255) DEFAULT 'HCP',
    url_source VARCHAR(512),
    date_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_annee (annee)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE 5: Panier IPC
-- ====================================
CREATE TABLE IF NOT EXISTS panier_ipc (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annee_base INT NOT NULL,
    categorie VARCHAR(100) NOT NULL,
    sous_categorie VARCHAR(200),
    ponderation DECIMAL(5, 2) NOT NULL,
    description TEXT,
    UNIQUE KEY unique_panier (annee_base, categorie, sous_categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- TABLE 6: Statistiques du site
-- ====================================
CREATE TABLE IF NOT EXISTS site_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    page_views INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    calculations INT DEFAULT 0,
    UNIQUE KEY unique_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- INSERTION DONNÉES DE BASE - Panier IPC 2017
-- ====================================
INSERT IGNORE INTO panier_ipc (annee_base, categorie, sous_categorie, ponderation) VALUES
(2017, 'Alimentation et boissons', 'Pain et céréales', 4.2),
(2017, 'Alimentation et boissons', 'Viandes', 5.8),
(2017, 'Alimentation et boissons', 'Poissons et fruits de mer', 2.1),
(2017, 'Alimentation et boissons', 'Lait, fromage et œufs', 3.5),
(2017, 'Alimentation et boissons', 'Huiles et graisses', 1.9),
(2017, 'Alimentation et boissons', 'Fruits', 2.4),
(2017, 'Alimentation et boissons', 'Légumes', 3.1),
(2017, 'Produits non alimentaires', 'Habillement et chaussures', 6.5),
(2017, 'Produits non alimentaires', 'Logement, eau, électricité', 15.2),
(2017, 'Produits non alimentaires', 'Meubles et articles ménagers', 5.3),
(2017, 'Produits non alimentaires', 'Santé', 3.8),
(2017, 'Produits non alimentaires', 'Transport', 14.7),
(2017, 'Produits non alimentaires', 'Communication', 4.2),
(2017, 'Produits non alimentaires', 'Loisirs et culture', 3.1),
(2017, 'Produits non alimentaires', 'Enseignement', 4.9),
(2017, 'Produits non alimentaires', 'Restaurants et hôtels', 5.6),
(2017, 'Produits non alimentaires', 'Biens et services divers', 4.7);

-- ====================================
-- INSERTION ÉVÉNEMENTS CONTEXTUELS
-- ====================================
INSERT IGNORE INTO metadata_inflation (annee, mois, evenement_contexte, source) VALUES
(2020, 3, 'Pandémie COVID-19 - Premier confinement national au Maroc', 'HCP'),
(2022, 2, 'Guerre Ukraine/Russie - Impact sur les prix de l\'énergie et des matières premières', 'HCP'),
(2022, 12, 'Pic d\'inflation à 8% - Plus haut niveau depuis des décennies', 'HCP'),
(2023, 6, 'Ralentissement de l\'inflation - Politique monétaire restrictive Bank Al-Maghrib', 'Bank Al-Maghrib'),
(2024, 12, 'Inflation maîtrisée autour de 1% - Retour à la stabilité', 'HCP'),
(2025, 1, 'Inflation historiquement basse (0.1%) - Désinflation marquée', 'HCP');