<?php
/**
 * Tests simples pour valider l'installation locale/CI.
 * - Vérifie la présence des dossiers clés
 * - Valide que les fichiers critiques se chargent sans erreur
 * - Optionnel : test de connexion MySQL si CHECK_DB=1
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

require_once ROOT . '/includes/config.php';
require_once ROOT . '/includes/database.php';

$failures = 0;
$skipped = 0;

function ok(bool $condition, string $label): void {
    global $failures;
    if ($condition) {
        echo "✅  {$label}\n";
    } else {
        $failures++;
        echo "❌  {$label}\n";
    }
}

function skip(string $label): void {
    global $skipped;
    $skipped++;
    echo "⏭️  {$label}\n";
}

// Présence des dossiers applicatifs
ok(is_dir(ROOT . '/public'), 'Répertoire public présent');
ok(is_dir(ROOT . '/includes'), 'Répertoire includes présent');
ok(is_dir(ROOT . '/data'), 'Répertoire data présent');

// Chargement de quelques fichiers critiques (parse-only)
ok((bool)include ROOT . '/includes/functions.php', 'Chargement des fonctions utilitaires');
ok((bool)include ROOT . '/includes/auth.php', 'Chargement auth/2FA');

// Connexion DB optionnelle
if (getenv('CHECK_DB') === '1') {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    ok($conn instanceof mysqli && $conn->connect_errno === 0, 'Connexion MySQL');
    if ($conn instanceof mysqli && $conn->connect_errno === 0) {
        $conn->close();
    } else {
        $failures++;
        echo "    Détails: " . ($conn->connect_error ?? 'Connexion impossible') . PHP_EOL;
    }
} else {
    skip('Connexion MySQL (CHECK_DB=1 pour activer)');
}

if ($failures > 0) {
    echo "Tests échoués: {$failures}\n";
    exit(1);
}

echo "Tests réussis. Skipped: {$skipped}\n";
