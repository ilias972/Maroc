-- Table pour les actualités économiques
CREATE TABLE IF NOT EXISTS actualites_economiques (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    source VARCHAR(100) NOT NULL,
    categorie VARCHAR(50),
    date_publication DATE NOT NULL,
    url_source VARCHAR(500),
    url_rapport VARCHAR(500),
    fichier_rapport VARCHAR(255),
    affiche BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_source (source),
    INDEX idx_date (date_publication),
    INDEX idx_affiche (affiche)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques actualités exemple
INSERT INTO actualites_economiques
(titre, description, source, categorie, date_publication, url_source, url_rapport) VALUES

('Note d''information relative à l''indice des prix à la consommation (IPC) - Décembre 2024',
'Publication mensuelle de l''IPC avec analyse détaillée des variations de prix par catégorie de produits.',
'HCP',
'Inflation',
'2025-01-10',
'https://www.hcp.ma/Indices-des-prix-a-la-consommation-IPC_r348.html',
'https://www.hcp.ma/downloads/IPC-Indice-des-prix-a-la-consommation_t12173.html'),

('Rapport sur la politique monétaire - T4 2024',
'Analyse de la conjoncture économique et monétaire, prévisions d''inflation et décisions du Conseil de Bank Al-Maghrib.',
'Bank Al-Maghrib',
'Politique Monétaire',
'2025-01-15',
'https://www.bkam.ma/Publications-et-recherche/Publications-periodiques/Rapport-sur-la-politique-monetaire',
'https://www.bkam.ma/content/download/833885/8854321/RPM-T4-2024.pdf'),

('Note de Conjoncture Économique - Décembre 2024',
'Évolution des principaux indicateurs économiques : croissance, emploi, commerce extérieur.',
'HCP',
'Conjoncture',
'2024-12-20',
'https://www.hcp.ma/Note-de-conjoncture_a2614.html',
NULL),

('Projet de Loi de Finances 2025',
'Présentation des grandes orientations budgétaires et mesures fiscales pour l''année 2025.',
'Ministère de l''Économie et des Finances',
'Budget',
'2024-10-15',
'https://www.finances.gov.ma/fr/Pages/loi-finances.aspx',
'https://www.finances.gov.ma/Docs/2025/PLF/PLF_2025.pdf'),

('Tableau de bord des indicateurs macroéconomiques',
'Synthèse mensuelle des principaux indicateurs : inflation, croissance, balance commerciale.',
'Ministère de l''Économie et des Finances',
'Indicateurs',
'2025-01-05',
'https://www.finances.gov.ma/fr/Pages/tdb-indicators.aspx',
NULL),

('Enquête Nationale sur l''Emploi - T3 2024',
'Résultats de l''enquête trimestrielle sur le marché du travail : taux de chômage, création d''emplois.',
'HCP',
'Emploi',
'2024-11-25',
'https://www.hcp.ma/Activite-emploi-et-chomage_r149.html',
'https://www.hcp.ma/downloads/Activite-emploi-et-chomage_t13076.html');