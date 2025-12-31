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

-- Les données d'inflation internationale doivent être importées depuis World Bank API
-- Utiliser: php data/import_world_bank.php
-- Pas de données d'exemple mockées ici