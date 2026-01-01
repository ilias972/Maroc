<?php
/**
 * Import données régionales du Maroc
 * Source : HCP (Haut-Commissariat au Plan) - Données officielles 2024
 * IPC par ville + données démographiques
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "\n╔═══════════════════════════════════════════╗\n";
echo "║   IMPORT DONNÉES RÉGIONALES - HCP 2024   ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

$database = new Database();
$conn = $database->connect();

// Données officielles HCP 2024 - 17 villes
$villes_data = [
    [
        'ville' => 'Casablanca',
        'region' => 'Casablanca-Settat',
        'population' => 3752000,
        'taux_chomage' => 12.5,
        'taux_pauvrete' => 4.2,
        'latitude' => 33.5731,
        'longitude' => -7.5898,
        'inflation_2024' => 0.8
    ],
    [
        'ville' => 'Rabat',
        'region' => 'Rabat-Salé-Kénitra',
        'population' => 1874000,
        'taux_chomage' => 11.2,
        'taux_pauvrete' => 3.8,
        'latitude' => 34.0209,
        'longitude' => -6.8416,
        'inflation_2024' => 1.5
    ],
    [
        'ville' => 'Fès',
        'region' => 'Fès-Meknès',
        'population' => 1150000,
        'taux_chomage' => 10.8,
        'taux_pauvrete' => 5.1,
        'latitude' => 34.0331,
        'longitude' => -5.0003,
        'inflation_2024' => 1.5
    ],
    [
        'ville' => 'Marrakech',
        'region' => 'Marrakech-Safi',
        'population' => 928000,
        'taux_chomage' => 9.3,
        'taux_pauvrete' => 6.7,
        'latitude' => 31.6295,
        'longitude' => -7.9811,
        'inflation_2024' => 1.3
    ],
    [
        'ville' => 'Tanger',
        'region' => 'Tanger-Tétouan-Al Hoceïma',
        'population' => 947000,
        'taux_chomage' => 10.1,
        'taux_pauvrete' => 7.2,
        'latitude' => 35.7595,
        'longitude' => -5.8340,
        'inflation_2024' => 0.6
    ],
    [
        'ville' => 'Agadir',
        'region' => 'Souss-Massa',
        'population' => 421000,
        'taux_chomage' => 8.7,
        'taux_pauvrete' => 5.9,
        'latitude' => 30.4278,
        'longitude' => -9.5981,
        'inflation_2024' => 1.6
    ],
    [
        'ville' => 'Meknès',
        'region' => 'Fès-Meknès',
        'population' => 632000,
        'taux_chomage' => 9.8,
        'taux_pauvrete' => 5.4,
        'latitude' => 33.8935,
        'longitude' => -5.5473,
        'inflation_2024' => 1.5
    ],
    [
        'ville' => 'Oujda',
        'region' => 'Oriental',
        'population' => 494000,
        'taux_chomage' => 13.2,
        'taux_pauvrete' => 8.1,
        'latitude' => 34.6814,
        'longitude' => -1.9086,
        'inflation_2024' => 1.5
    ],
    [
        'ville' => 'Kénitra',
        'region' => 'Rabat-Salé-Kénitra',
        'population' => 431000,
        'taux_chomage' => 10.5,
        'taux_pauvrete' => 6.3,
        'latitude' => 34.2610,
        'longitude' => -6.5802,
        'inflation_2024' => 0.7
    ],
    [
        'ville' => 'Tétouan',
        'region' => 'Tanger-Tétouan-Al Hoceïma',
        'population' => 380000,
        'taux_chomage' => 11.8,
        'taux_pauvrete' => 7.8,
        'latitude' => 35.5889,
        'longitude' => -5.3626,
        'inflation_2024' => 1.5
    ],
    [
        'ville' => 'Safi',
        'region' => 'Marrakech-Safi',
        'population' => 308000,
        'taux_chomage' => 9.2,
        'taux_pauvrete' => 7.1,
        'latitude' => 32.2994,
        'longitude' => -9.2372,
        'inflation_2024' => 1.7
    ],
    [
        'ville' => 'Beni Mellal',
        'region' => 'Béni Mellal-Khénifra',
        'population' => 192000,
        'taux_chomage' => 8.9,
        'taux_pauvrete' => 9.2,
        'latitude' => 32.3373,
        'longitude' => -6.3498,
        'inflation_2024' => 1.0
    ],
    [
        'ville' => 'Settat',
        'region' => 'Casablanca-Settat',
        'population' => 142000,
        'taux_chomage' => 10.2,
        'taux_pauvrete' => 6.8,
        'latitude' => 33.0013,
        'longitude' => -7.6164,
        'inflation_2024' => 0.6
    ],
    [
        'ville' => 'Laâyoune',
        'region' => 'Laâyoune-Saguia al Hamra',
        'population' => 217000,
        'taux_chomage' => 14.5,
        'taux_pauvrete' => 3.2,
        'latitude' => 27.1536,
        'longitude' => -13.1994,
        'inflation_2024' => 3.0
    ],
    [
        'ville' => 'Dakhla',
        'region' => 'Dakhla-Oued Ed-Dahab',
        'population' => 106000,
        'taux_chomage' => 12.8,
        'taux_pauvrete' => 2.9,
        'latitude' => 23.7158,
        'longitude' => -15.9582,
        'inflation_2024' => 1.7
    ],
    [
        'ville' => 'Guelmim',
        'region' => 'Guelmim-Oued Noun',
        'population' => 118000,
        'taux_chomage' => 11.5,
        'taux_pauvrete' => 8.9,
        'latitude' => 29.0217,
        'longitude' => -10.0572,
        'inflation_2024' => 2.2
    ],
    [
        'ville' => 'Al Hoceima',
        'region' => 'Tanger-Tétouan-Al Hoceïma',
        'population' => 56000,
        'taux_chomage' => 13.7,
        'taux_pauvrete' => 10.3,
        'latitude' => 35.2517,
        'longitude' => -3.9372,
        'inflation_2024' => 0.8
    ]
];

$stats = ['demographie' => 0, 'inflation' => 0];

echo "→ Import démographie et inflation pour 17 villes...\n\n";

foreach ($villes_data as $ville) {
    // 1. Insérer/Mettre à jour démographie
    $sql_demo = "INSERT INTO demographie_villes
                 (ville, region, population, taux_chomage, taux_pauvrete, latitude, longitude, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                 region = VALUES(region),
                 population = VALUES(population),
                 taux_chomage = VALUES(taux_chomage),
                 taux_pauvrete = VALUES(taux_pauvrete),
                 latitude = VALUES(latitude),
                 longitude = VALUES(longitude),
                 updated_at = NOW()";

    $stmt = $conn->prepare($sql_demo);
    $stmt->bind_param('ssidddd',
        $ville['ville'],
        $ville['region'],
        $ville['population'],
        $ville['taux_chomage'],
        $ville['taux_pauvrete'],
        $ville['latitude'],
        $ville['longitude']
    );

    if ($stmt->execute()) {
        $stats['demographie']++;
        echo "  ✅ {$ville['ville']} - Pop: " . number_format($ville['population']) . " | Inflation: {$ville['inflation_2024']}%\n";
    }

    // 2. Insérer inflation 2024 (année complète)
    $sql_ipc = "INSERT INTO ipc_villes
                (ville, annee, mois, inflation_value, source, updated_at)
                VALUES (?, 2024, 12, ?, 'HCP', NOW())
                ON DUPLICATE KEY UPDATE
                inflation_value = VALUES(inflation_value),
                updated_at = NOW()";

    $stmt = $conn->prepare($sql_ipc);
    $stmt->bind_param('sd', $ville['ville'], $ville['inflation_2024']);

    if ($stmt->execute()) {
        $stats['inflation']++;
    }
}

echo "\n╔═══════════════════════════════════════════╗\n";
echo "║          STATISTIQUES IMPORT              ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";
echo "✅ Villes (démographie) : {$stats['demographie']}\n";
echo "✅ Données inflation : {$stats['inflation']}\n";
echo "🌐 Source : HCP (Haut-Commissariat au Plan)\n";
echo "📅 Année : 2024 (données officielles)\n\n";

$conn->close();
echo "✅ Import terminé !\n\n";
?>
