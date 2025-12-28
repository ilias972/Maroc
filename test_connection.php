<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "=== TEST DE CONNEXION ===\n\n";

echo "Configuration :\n";
echo "- DB_HOST: " . DB_HOST . "\n";
echo "- DB_NAME: " . DB_NAME . "\n";
echo "- DB_USER: " . DB_USER . "\n";
echo "- SITE_NAME: " . SITE_NAME . "\n";
echo "- APP_ENV: " . APP_ENV . "\n\n";

try {
    $database = new Database();
    $conn = $database->connect();
    
    if ($conn->ping()) {
        echo "✅ Connexion à MySQL réussie !\n\n";
        
        // Tester une requête
        $result = $conn->query("SELECT COUNT(*) as count FROM panier_ipc");
        $row = $result->fetch_assoc();
        echo "✅ Test requête : {$row['count']} enregistrements dans panier_ipc\n\n";
        
        // Lister les tables
        $result = $conn->query("SHOW TABLES");
        echo "Tables disponibles :\n";
        while ($row = $result->fetch_array()) {
            echo "  - {$row[0]}\n";
        }
        
        echo "\n✅ TOUS LES TESTS RÉUSSIS !\n";
        
    } else {
        echo "❌ Connexion échouée\n";
    }
    
    $database->close();
    
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
}
?>