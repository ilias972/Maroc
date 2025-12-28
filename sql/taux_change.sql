-- Table pour les taux de change Bank Al-Maghrib
DROP TABLE IF EXISTS taux_change;

CREATE TABLE taux_change (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date_taux DATE NOT NULL,
    devise VARCHAR(3) NOT NULL,
    type_taux ENUM('BBE', 'VIREMENT') NOT NULL DEFAULT 'BBE',
    cours_mad DECIMAL(10, 4) NOT NULL,
    cours_achat DECIMAL(10, 4) NULL,
    cours_vente DECIMAL(10, 4) NULL,
    source VARCHAR(100) DEFAULT 'Bank Al-Maghrib API',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_taux (date_taux, devise, type_taux),
    INDEX idx_date (date_taux),
    INDEX idx_devise (devise)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;