<?php
/**
 * API - Récupérer les taux de change
 *
 * Retour: JSON avec les taux de change disponibles
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

try {
    $database = new Database();
    $conn = $database->connect();

    // Récupérer le dernier taux EUR
    $eur_taux = getDernierTauxChange('EUR', 'VIREMENT');

    // Récupérer les autres devises
    $devises = ['USD', 'GBP', 'CHF'];
    $autres_taux = [];

    foreach ($devises as $devise) {
        $taux = getDernierTauxChange($devise, 'VIREMENT');
        if ($taux) {
            $autres_taux[] = [
                'devise' => $devise,
                'cours_mad' => $taux['cours_mad'],
                'date_taux' => $taux['date_taux'],
                'jours_ecart' => $taux['jours_ecart'],
                'is_recent' => $taux['is_recent'],
                'jour_semaine' => $taux['jour_semaine']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'taux' => [
            'eur' => $eur_taux,
            'autres' => $autres_taux
        ],
        'source' => 'ExchangeRate-API',
        'derniere_maj' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>