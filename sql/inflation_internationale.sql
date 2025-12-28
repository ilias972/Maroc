-- Table pour les données d'inflation internationale (World Bank)
CREATE TABLE IF NOT EXISTS inflation_internationale (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pays_code VARCHAR(3) NOT NULL,
    pays_nom VARCHAR(100) NOT NULL,
    annee INT NOT NULL,
    inflation_value DECIMAL(5, 2),
    source VARCHAR(100) DEFAULT 'World Bank API',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_pays_annee (pays_code, annee),
    INDEX idx_pays (pays_code),
    INDEX idx_annee (annee)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;