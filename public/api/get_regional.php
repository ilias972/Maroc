<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../../includes/config.php';
require_once '../../includes/database.php';

try {
    $database = new Database();
    $conn = $database->connect();

    // Paramètres
    $annee = isset($_GET['annee']) ? intval($_GET['annee']) : 2024;
    $mois = isset($_GET['mois']) ? intval($_GET['mois']) : 12;

    // Récupérer inflation par ville
    $sql_inf = "SELECT ville, inflation_value
                FROM ipc_villes
                WHERE annee = ? AND mois = ?";
    $stmt = $conn->prepare($sql_inf);
    $stmt->bind_param('ii', $annee, $mois);
    $stmt->execute();
    $result_inf = $stmt->get_result();

    $inflation_map = [];
    while ($row = $result_inf->fetch_assoc()) {
        $inflation_map[$row['ville']] = floatval($row['inflation_value']);
    }

    // Récupérer données démographiques
    $sql_demo = "SELECT * FROM demographie_villes ORDER BY population DESC";
    $result_demo = $conn->query($sql_demo);

    $villes = [];
    while ($row = $result_demo->fetch_assoc()) {
        $ville_nom = $row['ville'];
        $inflation = isset($inflation_map[$ville_nom]) ? $inflation_map[$ville_nom] : null;

        $villes[] = [
            'ville' => $ville_nom,
            'region' => $row['region'],
            'population' => intval($row['population']),
            'chomage' => round(floatval($row['taux_chomage']), 2),
            'pauvrete' => round(floatval($row['taux_pauvrete']), 2),
            'inflation' => $inflation,
            'coords' => [
                'lat' => round(floatval($row['latitude']), 4),
                'lng' => round(floatval($row['longitude']), 4)
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'periode' => [
            'annee' => $annee,
            'mois' => $mois
        ],
        'villes' => $villes,
        'total_villes' => count($villes)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>