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

-- Insérer les 17 villes HCP avec coordonnées GPS et données démographiques
INSERT IGNORE INTO demographie_villes (ville, region, population, taux_chomage, taux_pauvrete, latitude, longitude) VALUES
('Casablanca', 'Casablanca-Settat', 3600000, 12.5, 8.2, 33.5731, -7.5898),
('Rabat', 'Rabat-Salé-Kénitra', 580000, 10.8, 6.5, 34.0209, -6.8416),
('Fès', 'Fès-Meknès', 1150000, 14.2, 12.3, 34.0331, -5.0003),
('Marrakech', 'Marrakech-Safi', 929000, 9.7, 10.5, 31.6295, -7.9811),
('Agadir', 'Souss-Massa', 422000, 11.3, 9.8, 30.4278, -9.5981),
('Tanger', 'Tanger-Tétouan-Al Hoceima', 948000, 13.5, 11.2, 35.7595, -5.8340),
('Meknès', 'Fès-Meknès', 632000, 13.8, 11.7, 33.8935, -5.5473),
('Oujda', 'Oriental', 495000, 15.6, 14.5, 34.6814, -1.9086),
('Kénitra', 'Rabat-Salé-Kénitra', 431000, 12.1, 10.8, 34.2610, -6.5802),
('Tétouan', 'Tanger-Tétouan-Al Hoceima', 381000, 14.7, 13.2, 35.5889, -5.3626),
('Laâyoune', 'Laâyoune-Sakia El Hamra', 217000, 8.5, 6.2, 27.1536, -13.1994),
('Dakhla', 'Dakhla-Oued Ed-Dahab', 107000, 7.2, 5.8, 23.7151, -15.9582),
('Guelmim', 'Guelmim-Oued Noun', 118000, 16.8, 15.3, 28.9870, -10.0574),
('Settat', 'Casablanca-Settat', 145000, 11.9, 9.5, 33.0013, -7.6164),
('Safi', 'Marrakech-Safi', 309000, 13.2, 11.8, 32.2994, -9.2372),
('Beni Mellal', 'Béni Mellal-Khénifra', 193000, 14.5, 13.7, 32.3394, -6.3498),
('Al Hoceima', 'Tanger-Tétouan-Al Hoceima', 56000, 17.2, 16.5, 35.2517, -3.9316);

-- Insérer des données exemple d'inflation par ville (décembre 2024)
INSERT IGNORE INTO ipc_villes (annee, mois, ville, inflation_value) VALUES
(2024, 12, 'Casablanca', 0.6),
(2024, 12, 'Rabat', 0.7),
(2024, 12, 'Fès', 0.9),
(2024, 12, 'Marrakech', 0.8),
(2024, 12, 'Agadir', 1.1),
(2024, 12, 'Tanger', 0.5),
(2024, 12, 'Meknès', 0.8),
(2024, 12, 'Oujda', 1.2),
(2024, 12, 'Kénitra', 0.7),
(2024, 12, 'Tétouan', 0.6),
(2024, 12, 'Laâyoune', 0.4),
(2024, 12, 'Dakhla', 0.3),
(2024, 12, 'Guelmim', 1.3),
(2024, 12, 'Settat', 0.6),
(2024, 12, 'Safi', 0.9),
(2024, 12, 'Beni Mellal', 1.0),
(2024, 12, 'Al Hoceima', 1.4);