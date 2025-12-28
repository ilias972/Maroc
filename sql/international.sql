-- Table pour les données d'inflation internationales
CREATE TABLE IF NOT EXISTS inflation_internationale (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pays VARCHAR(100) NOT NULL,
    code_pays VARCHAR(3) NOT NULL, -- ISO 3166-1 alpha-3
    annee INT NOT NULL,
    mois INT NOT NULL,
    inflation_annuelle DECIMAL(5, 2),
    source VARCHAR(255) DEFAULT 'Trading Economics',
    date_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pays_periode (code_pays, annee, mois),
    INDEX idx_pays (code_pays),
    INDEX idx_date (annee, mois)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer quelques pays de référence
INSERT IGNORE INTO inflation_internationale (pays, code_pays, annee, mois, inflation_annuelle) VALUES
-- France (données exemple - à remplacer par vraies données)
('France', 'FRA', 2024, 12, 1.3),
('France', 'FRA', 2024, 11, 1.4),
('France', 'FRA', 2024, 10, 1.5),
-- Espagne
('Espagne', 'ESP', 2024, 12, 2.8),
('Espagne', 'ESP', 2024, 11, 2.9),
('Espagne', 'ESP', 2024, 10, 3.0),
-- Algérie
('Algérie', 'DZA', 2024, 12, 4.5),
('Algérie', 'DZA', 2024, 11, 4.6),
('Algérie', 'DZA', 2024, 10, 4.7),
-- Tunisie
('Tunisie', 'TUN', 2024, 12, 6.2),
('Tunisie', 'TUN', 2024, 11, 6.3),
('Tunisie', 'TUN', 2024, 10, 6.4),
-- Allemagne
('Allemagne', 'DEU', 2024, 12, 2.2),
('Allemagne', 'DEU', 2024, 11, 2.3),
('Allemagne', 'DEU', 2024, 10, 2.4);