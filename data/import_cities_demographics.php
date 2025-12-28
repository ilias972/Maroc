<?php
/**
 * Import données démographiques RÉELLES via APIs
 * RÈGLE : Aucune donnée hardcodée - Si API ne retourne rien = laisser NULL
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║    IMPORT DONNÉES DÉMOGRAPHIQUES RÉELLES - VILLES MAROC   ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Configuration APIs
$api_cities_url = 'https://world-cities-database.p.rapidapi.com/api/data/world-cities-database/v1';
$api_key = 'f444f573f6msh9bdfeb16607d1cbp1b7692jsn896bbe376f45';

// Liste des 17 villes
$villes_principales = [
    'Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Tanger', 'Agadir',
    'Meknès', 'Oujda', 'Kénitra', 'Tétouan', 'Safi', 'Beni Mellal',
    'El Jadida', 'Nador', 'Khouribga', 'Settat', 'Laâyoune'
];

// ═══════════════════════════════════════════════════════════
// APPEL API WORLD CITIES DATABASE
// ═══════════════════════════════════════════════════════════

echo "🌍 Appel API World Cities Database...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_cities_url . '?country=morocco&limit=100',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'x-rapidapi-host: world-cities-database.p.rapidapi.com',
        'x-rapidapi-key: ' . $api_key
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo "❌ Erreur API (Code : $http_code)\n";
    echo "Impossible de récupérer les données.\n\n";
    exit;
}

$data = json_decode($response, true);

if (!isset($data['data'])) {
    echo "❌ Format de réponse invalide\n\n";
    exit;
}

$cities_api = $data['data'];
echo "✅ API répondu : " . count($cities_api) . " villes reçues\n\n";

// ═══════════════════════════════════════════════════════════
// TRAITEMENT DES DONNÉES - AUCUNE INVENTION
// ═══════════════════════════════════════════════════════════

$database = new Database();
$conn = $database->connect();

$donnees_trouvees = 0;
$donnees_manquantes = 0;
$villes_manquantes = [];

echo "📊 Mise à jour des données RÉELLES uniquement...\n\n";

foreach ($villes_principales as $ville_recherchee) {
    // Chercher la ville dans les données API
    $ville_trouvee = null;

    foreach ($cities_api as $city) {
        if (strcasecmp($city['city'] ?? '', $ville_recherchee) === 0) {
            $ville_trouvee = $city;
            break;
        }
    }

    if ($ville_trouvee) {
        // Données trouvées dans l'API
        $population = isset($ville_trouvee['population']) && $ville_trouvee['population'] > 0
                      ? intval($ville_trouvee['population'])
                      : null;

        $latitude = isset($ville_trouvee['latitude'])
                    ? floatval($ville_trouvee['latitude'])
                    : null;

        $longitude = isset($ville_trouvee['longitude'])
                     ? floatval($ville_trouvee['longitude'])
                     : null;

        $region = !empty($ville_trouvee['admin_name'])
                  ? $ville_trouvee['admin_name']
                  : null;

        // Mettre à jour UNIQUEMENT si données valides
        if ($population !== null && $latitude !== null && $longitude !== null) {
            $sql = "UPDATE demographie_villes
                    SET population = ?,
                        latitude = ?,
                        longitude = ?,
                        region = ?
                    WHERE ville = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iddss', $population, $latitude, $longitude, $region, $ville_recherchee);
            $stmt->execute();

            echo "✅ $ville_recherchee : Pop=" . number_format($population) .
                 " | GPS=($latitude, $longitude)" .
                 ($region ? " | Région=$region" : "") . "\n";

            $donnees_trouvees++;
        } else {
            // Données incomplètes - laisser NULL
            echo "⚠️  $ville_recherchee : Données incomplètes dans l'API (champs laissés vides)\n";
            $donnees_manquantes++;
            $villes_manquantes[] = $ville_recherchee;
        }
    } else {
        // Ville non trouvée dans l'API - laisser NULL
        echo "❌ $ville_recherchee : NON TROUVÉE dans l'API (champs laissés vides)\n";
        $donnees_manquantes++;
        $villes_manquantes[] = $ville_recherchee;
    }
}

$conn->close();

// ═══════════════════════════════════════════════════════════
// RAPPORT FINAL
// ═══════════════════════════════════════════════════════════

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    RAPPORT FINAL                          ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "📊 Statistiques :\n";
echo "   - Villes avec données complètes : $donnees_trouvees / " . count($villes_principales) . "\n";
echo "   - Villes avec données manquantes : $donnees_manquantes / " . count($villes_principales) . "\n\n";

if (!empty($villes_manquantes)) {
    echo "⚠️  Villes avec données manquantes (laissées vides) :\n";
    foreach ($villes_manquantes as $ville) {
        echo "   - $ville\n";
    }
    echo "\n";
}

echo "✅ Import terminé\n";
echo "📌 Source : API World Cities Database (RapidAPI)\n";
echo "📌 Date : " . date('Y-m-d H:i:s') . "\n\n";

echo "⚠️  IMPORTANT : Aucune donnée fictive n'a été ajoutée.\n";
echo "   Les champs vides indiquent l'absence de données dans l'API.\n\n";

// Sauvegarder rapport
$rapport = "# RAPPORT IMPORT DONNÉES DÉMOGRAPHIQUES\n\n";
$rapport .= "Date : " . date('Y-m-d H:i:s') . "\n";
$rapport .= "Source : API World Cities Database\n\n";
$rapport .= "## Résultats\n\n";
$rapport .= "Villes avec données : $donnees_trouvees / " . count($villes_principales) . "\n";
$rapport .= "Villes sans données : $donnees_manquantes / " . count($villes_principales) . "\n\n";

if (!empty($villes_manquantes)) {
    $rapport .= "## Villes avec données manquantes\n\n";
    foreach ($villes_manquantes as $ville) {
        $rapport .= "- $ville\n";
    }
    $rapport .= "\n";
}

$rapport .= "## Note\n\n";
$rapport .= "Aucune donnée fictive n'a été insérée.\n";
$rapport .= "Les champs laissés vides (NULL) indiquent que l'API n'a pas retourné de données pour ces villes.\n";

file_put_contents(__DIR__ . '/RAPPORT_IMPORT_DEMOGRAPHICS.txt', $rapport);
echo "📄 Rapport sauvegardé : data/RAPPORT_IMPORT_DEMOGRAPHICS.txt\n\n";
?>