<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../../includes/config.php';
require_once '../../includes/database.php';

try {
    $database = new Database();
    $conn = $database->connect();

    // Récupérer toutes les prévisions
    $sql = "SELECT annee, mois, inflation_prevue, inflation_min, inflation_max, date_calcul
            FROM previsions_inflation
            ORDER BY annee ASC, mois ASC";

    $result = $conn->query($sql);

    $previsions = [];
    while ($row = $result->fetch_assoc()) {
        $previsions[] = [
            'date' => $row['annee'] . '-' . str_pad($row['mois'], 2, '0', STR_PAD_LEFT),
            'annee' => intval($row['annee']),
            'mois' => intval($row['mois']),
            'prevision' => round(floatval($row['inflation_prevue']), 2),
            'min' => round(floatval($row['inflation_min']), 2),
            'max' => round(floatval($row['inflation_max']), 2),
            'calcule_le' => date('Y-m-d', strtotime($row['date_calcul']))
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($previsions),
        'previsions' => $previsions,
        'avertissement' => 'Les prévisions sont basées sur des modèles statistiques simples et ne garantissent pas les valeurs futures réelles.'
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