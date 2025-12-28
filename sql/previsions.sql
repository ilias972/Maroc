-- Table pour les prévisions d'inflation
CREATE TABLE IF NOT EXISTS previsions_inflation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    annee INT NOT NULL,
    mois INT NOT NULL,
    inflation_prevue DECIMAL(5, 2),
    inflation_min DECIMAL(5, 2), -- Borne inférieure
    inflation_max DECIMAL(5, 2), -- Borne supérieure
    methode VARCHAR(50) DEFAULT 'moyenne_mobile',
    date_calcul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_periode (annee, mois),
    INDEX idx_date (annee, mois)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;