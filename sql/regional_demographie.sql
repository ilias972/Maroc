-- Table pour les données démographiques par ville/région
CREATE TABLE IF NOT EXISTS demographie_villes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ville VARCHAR(100) NOT NULL,
    region VARCHAR(100),
    population INT,
    taux_chomage DECIMAL(5, 2),
    taux_pauvrete DECIMAL(5, 2),
    pib_par_habitant DECIMAL(10, 2),
    annee_donnees INT DEFAULT 2024,
    latitude DECIMAL(10, 7),
    longitude DECIMAL(10, 7),
    source VARCHAR(255) DEFAULT 'HCP',
    date_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ville_annee (ville, annee_donnees)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les 17 villes HCP avec coordonnées GPS et régions
-- Population, taux chômage, pauvreté : NULL (à importer depuis sources officielles)
INSERT IGNORE INTO demographie_villes (ville, region, latitude, longitude, source) VALUES
('Casablanca', 'Casablanca-Settat', 33.5731, -7.5898, 'GPS'),
('Rabat', 'Rabat-Salé-Kénitra', 34.0209, -6.8416, 'GPS'),
('Fès', 'Fès-Meknès', 34.0331, -5.0003, 'GPS'),
('Marrakech', 'Marrakech-Safi', 31.6295, -7.9811, 'GPS'),
('Agadir', 'Souss-Massa', 30.4278, -9.5981, 'GPS'),
('Tanger', 'Tanger-Tétouan-Al Hoceima', 35.7595, -5.8340, 'GPS'),
('Meknès', 'Fès-Meknès', 33.8935, -5.5473, 'GPS'),
('Oujda', 'Oriental', 34.6814, -1.9086, 'GPS'),
('Kénitra', 'Rabat-Salé-Kénitra', 34.2610, -6.5802, 'GPS'),
('Tétouan', 'Tanger-Tétouan-Al Hoceima', 35.5889, -5.3626, 'GPS'),
('Laâyoune', 'Laâyoune-Sakia El Hamra', 27.1536, -13.1994, 'GPS'),
('Dakhla', 'Dakhla-Oued Ed-Dahab', 23.7151, -15.9582, 'GPS'),
('Guelmim', 'Guelmim-Oued Noun', 28.9870, -10.0574, 'GPS'),
('Settat', 'Casablanca-Settat', 33.0013, -7.6164, 'GPS'),
('Safi', 'Marrakech-Safi', 32.2994, -9.2372, 'GPS'),
('Beni Mellal', 'Béni Mellal-Khénifra', 32.3394, -6.3498, 'GPS'),
('Al Hoceima', 'Tanger-Tétouan-Al Hoceima', 35.2517, -3.9316, 'GPS');

-- Les données d'inflation par ville doivent être importées depuis les rapports HCP officiels
-- Pas de données d'exemple mockées ici