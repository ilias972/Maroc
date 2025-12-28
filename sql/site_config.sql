-- Table de configuration système
CREATE TABLE IF NOT EXISTS site_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer configurations par défaut
INSERT IGNORE INTO site_config (config_key, value, description) VALUES
('site_name', 'Maroc Inflation', 'Nom du site'),
('site_version', '1.0.0', 'Version du site'),
('last_cron_run', NULL, 'Dernière exécution du cron'),
('last_cron_stats', NULL, 'Statistiques dernière exécution cron'),
('last_hcp_import', NULL, 'Dernière import HCP'),
('maintenance_mode', 'false', 'Mode maintenance'),
('cache_enabled', 'true', 'Cache activé'),
('debug_mode', 'false', 'Mode debug');