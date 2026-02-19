<?php
/**
 * Configuration générale du site Maroc Inflation
 */

// Charger les variables d'environnement depuis .env (version améliorée)
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    // Avertissement de sécurité si le fichier est accessible publiquement
    if (isset($_SERVER['DOCUMENT_ROOT']) && strpos(realpath($env_file), realpath($_SERVER['DOCUMENT_ROOT'])) === 0) {
        error_log("AVERTISSEMENT SÉCURITÉ : Le fichier .env est situé dans le répertoire web public !");
    }

    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignorer les commentaires complets et s'assurer qu'il y a un signe égal
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        // Nettoyer la valeur (enlever les commentaires de fin de ligne et les guillemets)
        $value = trim(explode('#', $value)[0]); 
        $value = trim($value, '"\'');
        
        $_ENV[$key] = $value;
        putenv("$key=$value");
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
