<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../../includes/config.php';
require_once '../../includes/database.php';

try {
    $database = new Database();
    $conn = $database->connect();

    // Paramètres
    $annee = isset($_GET['annee']) ? intval($_GET['annee']) : CURRENT_YEAR;
    $mois = isset($_GET['mois']) ? intval($_GET['mois']) : 12;

    // Récupérer Maroc
    $sql_mar = "SELECT annee, mois, inflation_annuelle
                FROM ipc_mensuel
                WHERE annee = ? AND mois = ?";
    $stmt = $conn->prepare($sql_mar);
    $annee_mar = $annee;
    $mois_mar = $mois;
    $stmt->bind_param('ii', $annee_mar, $mois_mar);
    $stmt->execute();
    $maroc = $stmt->get_result()->fetch_assoc();

    // Récupérer pays internationaux (exclure Maroc car déjà dans ipc_mensuel)
    $sql_int = "SELECT pays, code_pays, inflation_annuelle
                FROM inflation_internationale
                WHERE annee = ? AND mois = ? AND code_pays != 'MAR'
                ORDER BY inflation_annuelle DESC";
    $stmt = $conn->prepare($sql_int);
    $annee_int = $annee;
    $mois_int = $mois;
    $stmt->bind_param('ii', $annee_int, $mois_int);
    $stmt->execute();
    $result = $stmt->get_result();

    $pays = [];
    while ($row = $result->fetch_assoc()) {
        $pays[] = [
            'pays' => $row['pays'],
            'code' => $row['code_pays'],
            'inflation' => round(floatval($row['inflation_annuelle']), 2)
        ];
    }

    // Ajouter Maroc si pas déjà dans la liste
    $maroc_data = [
        'pays' => 'Maroc',
        'code' => 'MAR',
        'inflation' => round(floatval($maroc['inflation_annuelle']), 2)
    ];

    // Insérer Maroc à la bonne position (trié par inflation)
    $inserted = false;
    foreach ($pays as $index => $p) {
        if ($maroc_data['inflation'] > $p['inflation']) {
            array_splice($pays, $index, 0, [$maroc_data]);
            $inserted = true;
            break;
        }
    }
    if (!$inserted) {
        $pays[] = $maroc_data;
    }

    echo json_encode([
        'success' => true,
        'periode' => [
            'annee' => $annee,
            'mois' => $mois
        ],
        'pays' => $pays
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