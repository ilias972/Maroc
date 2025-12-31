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

-- Les actualités doivent être ajoutées via l'interface admin (admin_actualites.php)
-- ou importées depuis des sources officielles via web scraping
-- Pas de données d'exemple mockées ici