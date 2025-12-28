<?php
/**
 * Nettoyage COMPLET des données mockées
 * ATTENTION : Irréversible !
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

$database = new Database();
$conn = $database->connect();

echo "\n╔═══════════════════════════════════════════╗\n";
echo "║    NETTOYAGE DONNÉES MOCKÉES              ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

echo "⚠️  SUPPRESSION de toutes les données de test\n\n";
echo "Confirmer ? (OUI) : ";
$confirm = trim(fgets(STDIN));

if (strtoupper($confirm) !== 'OUI') {
    echo "❌ Annulé\n";
    exit;
}

echo "\n🧹 Nettoyage...\n\n";

// Inflation internationale (garder World Bank)
echo "→ inflation_internationale...\n";
$conn->query("DELETE FROM inflation_internationale WHERE source != 'World Bank API' OR source IS NULL");
echo "  ✅ " . $conn->affected_rows . " supprimés\n\n";

// IPC mensuel (garder HCP)
echo "→ ipc_mensuel...\n";
$conn->query("DELETE FROM ipc_mensuel WHERE source IS NULL OR source NOT LIKE '%HCP%'");
echo "  ✅ " . $conn->affected_rows . " supprimés\n\n";

// Catégories IPC
echo "→ ipc_categories...\n";
$conn->query("TRUNCATE TABLE ipc_categories");
echo "  ✅ Vidé\n\n";

// Actualités test
echo "→ actualites_economiques...\n";
$conn->query("DELETE FROM actualites_economiques WHERE url_source IS NULL OR titre LIKE '%Exemple%'");
echo "  ✅ " . $conn->affected_rows . " supprimés\n\n";

// Prévisions
echo "→ previsions_inflation...\n";
$conn->query("TRUNCATE TABLE previsions_inflation");
echo "  ✅ Vidé\n\n";

echo "✅ Nettoyage terminé !\n\n";
$conn->close();
?>