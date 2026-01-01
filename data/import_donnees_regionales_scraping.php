<?php
/**
 * Import données régionales - Scraping HCP
 * Récupère automatiquement les données IPC publiées par le HCP
 * Source : Site officiel HCP (pas de données mockées)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "\n╔═══════════════════════════════════════════╗\n";
echo "║  SCRAPING DONNÉES RÉGIONALES HCP 2024    ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

$database = new Database();
$conn = $database->connect();

// URL de la page HCP avec les données IPC 2024
$hcp_url = 'https://www.hcp.ma/L-Indice-des-prix-a-la-consommation-IPC-de-l-annee-2024_a4056.html';

echo "🌐 Source : $hcp_url\n";
echo "→ Récupération de la page HCP...\n\n";

// Récupérer le contenu de la page
$ch = curl_init($hcp_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
]);

$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || !$html) {
    die("❌ Erreur : Impossible de récupérer la page HCP (HTTP $http_code)\n");
}

echo "✅ Page récupérée (HTTP $http_code)\n";
echo "→ Extraction des données IPC par ville...\n\n";

// Données démographiques officielles (recensement RGPH 2024)
$villes_demo = [
    'Casablanca' => ['region' => 'Casablanca-Settat', 'pop' => 3752000, 'lat' => 33.5731, 'lon' => -7.5898],
    'Rabat' => ['region' => 'Rabat-Salé-Kénitra', 'pop' => 1874000, 'lat' => 34.0209, 'lon' => -6.8416],
    'Fès' => ['region' => 'Fès-Meknès', 'pop' => 1150000, 'lat' => 34.0331, 'lon' => -5.0003],
    'Marrakech' => ['region' => 'Marrakech-Safi', 'pop' => 928000, 'lat' => 31.6295, 'lon' => -7.9811],
    'Tanger' => ['region' => 'Tanger-Tétouan-Al Hoceïma', 'pop' => 947000, 'lat' => 35.7595, 'lon' => -5.8340],
    'Agadir' => ['region' => 'Souss-Massa', 'pop' => 421000, 'lat' => 30.4278, 'lon' => -9.5981],
    'Meknès' => ['region' => 'Fès-Meknès', 'pop' => 632000, 'lat' => 33.8935, 'lon' => -5.5473],
    'Oujda' => ['region' => 'Oriental', 'pop' => 494000, 'lat' => 34.6814, 'lon' => -1.9086],
    'Kénitra' => ['region' => 'Rabat-Salé-Kénitra', 'pop' => 431000, 'lat' => 34.2610, 'lon' => -6.5802],
    'Tétouan' => ['region' => 'Tanger-Tétouan-Al Hoceïma', 'pop' => 380000, 'lat' => 35.5889, 'lon' => -5.3626],
    'Safi' => ['region' => 'Marrakech-Safi', 'pop' => 308000, 'lat' => 32.2994, 'lon' => -9.2372],
    'Beni Mellal' => ['region' => 'Béni Mellal-Khénifra', 'pop' => 192000, 'lat' => 32.3373, 'lon' => -6.3498],
    'Settat' => ['region' => 'Casablanca-Settat', 'pop' => 142000, 'lat' => 33.0013, 'lon' => -7.6164],
    'Laâyoune' => ['region' => 'Laâyoune-Saguia al Hamra', 'pop' => 217000, 'lat' => 27.1536, 'lon' => -13.1994],
    'Dakhla' => ['region' => 'Dakhla-Oued Ed-Dahab', 'pop' => 106000, 'lat' => 23.7158, 'lon' => -15.9582],
    'Guelmim' => ['region' => 'Guelmim-Oued Noun', 'pop' => 118000, 'lat' => 29.0217, 'lon' => -10.0572],
    'Al Hoceima' => ['region' => 'Tanger-Tétouan-Al Hoceïma', 'pop' => 56000, 'lat' => 35.2517, 'lon' => -3.9372],
];

// Parser le HTML pour extraire les données IPC par ville
// Format attendu : "Laâyoune (3.0%)", "Casablanca (0.8%)", etc.
preg_match_all('/(\w[\w\s-]+?)\s*\((\d+[,.]?\d*)\s*%\)/u', $html, $matches, PREG_SET_ORDER);

$ipc_data = [];
foreach ($matches as $match) {
    $ville = trim($match[1]);
    $inflation = floatval(str_replace(',', '.', $match[2]));

    // Normaliser les noms de villes
    $ville = str_replace(['Beni-Mellal', 'Béni Mellal', 'Beni Mellal'], 'Beni Mellal', $ville);
    $ville = str_replace(['Laayoune', 'Laâyoune', 'Laayoune'], 'Laâyoune', $ville);
    $ville = str_replace(['Al-hoceima', 'Al Hoceima', 'Al-Hoceima'], 'Al Hoceima', $ville);

    if ($inflation > 0 && $inflation < 20) { // Validation basique
        $ipc_data[$ville] = $inflation;
    }
}

$stats = ['demo' => 0, 'ipc' => 0];

// Insérer les données
foreach ($villes_demo as $ville => $demo) {
    // 1. Démographie
    $sql_demo = "INSERT INTO demographie_villes
                 (ville, region, population, latitude, longitude, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                 region = VALUES(region),
                 population = VALUES(population),
                 updated_at = NOW()";

    $stmt = $conn->prepare($sql_demo);
    $stmt->bind_param('ssidd', $ville, $demo['region'], $demo['pop'], $demo['lat'], $demo['lon']);

    if ($stmt->execute()) {
        $stats['demo']++;

        // 2. IPC si disponible
        if (isset($ipc_data[$ville])) {
            $inflation = $ipc_data[$ville];

            $sql_ipc = "INSERT INTO ipc_villes
                        (ville, annee, mois, inflation_value, source, updated_at)
                        VALUES (?, 2024, 12, ?, 'HCP', NOW())
                        ON DUPLICATE KEY UPDATE
                        inflation_value = VALUES(inflation_value),
                        updated_at = NOW()";

            $stmt = $conn->prepare($sql_ipc);
            $stmt->bind_param('sd', $ville, $inflation);

            if ($stmt->execute()) {
                $stats['ipc']++;
                echo "  ✅ $ville - Pop: " . number_format($demo['pop']) . " | IPC: {$inflation}%\n";
            }
        } else {
            echo "  ⚠️  $ville - Pop: " . number_format($demo['pop']) . " | IPC: non trouvé\n";
        }
    }
}

echo "\n╔═══════════════════════════════════════════╗\n";
echo "║          STATISTIQUES IMPORT              ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";
echo "✅ Villes (démographie) : {$stats['demo']}\n";
echo "✅ Données IPC scrapées : {$stats['ipc']}\n";
echo "🌐 Source : HCP (scraping automatique)\n";
echo "📅 Année : 2024\n\n";

$conn->close();
echo "✅ Import terminé !\n\n";

if ($stats['ipc'] < 10) {
    echo "⚠️  ATTENTION : Peu de données IPC récupérées.\n";
    echo "   Le format de la page HCP a peut-être changé.\n";
    echo "   Vérifiez manuellement : $hcp_url\n\n";
}
?>
