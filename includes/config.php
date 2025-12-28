<?php
/**
 * Configuration générale du site Maroc Inflation
 */

// Charger les variables d'environnement depuis .env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Enlever les guillemets si présents
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Configuration base de données
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'maroc_inflation');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Configuration site
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Maroc Inflation');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost:8000');
define('BASE_YEAR', intval($_ENV['BASE_YEAR'] ?? 2017));
define('START_YEAR', intval($_ENV['START_YEAR'] ?? 2007));
define('CURRENT_YEAR', date('Y'));

// Timezone
date_default_timezone_set('Africa/Casablanca');

// Environnement
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Gestion des erreurs
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php-errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
?>