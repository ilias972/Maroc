<?php
/**
 * Nettoyage des fausses données démographiques
 * Préparation pour import de vraies données via API
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║    NETTOYAGE DONNÉES DÉMOGRAPHIQUES FAUSSES               ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "🧹 Suppression de toutes les données démographiques actuelles...\n";
echo "   (Préparation pour import de vraies données via API)\n\n";

$database = new Database();
$conn = $database->connect();

$sql = "UPDATE demographie_villes
        SET population = NULL,
            latitude = NULL,
            longitude = NULL,
            region = NULL,
            taux_chomage = NULL";

if ($conn->query($sql)) {
    echo "✅ Toutes les données démographiques ont été effacées\n";
    echo "   Les champs sont maintenant à NULL\n\n";

    // Vérifier le résultat
    $result = $conn->query("SELECT COUNT(*) as total FROM demographie_villes WHERE population IS NOT NULL");
    $row = $result->fetch_assoc();
    echo "📊 Vérification : " . $row['total'] . " villes avec population (devrait être 0)\n\n";

} else {
    echo "❌ Erreur lors du nettoyage : " . $conn->error . "\n\n";
}

$conn->close();

echo "✅ Prêt pour l'import de vraies données via API World Cities Database\n\n";
echo "📌 Prochaine étape : php data/import_cities_demographics.php\n\n";
?>