<?php
/**
 * API - Récupérer l'historique IPC
 *
 * Paramètres GET:
 * - annee_debut (optionnel) : année de début
 * - annee_fin (optionnel) : année de fin
 *
 * Retour: JSON avec les données IPC
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
    $calculator = new InflationCalculator($conn);

    // Récupérer les paramètres
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
    $annee_debut = isset($_GET['annee_debut']) ? intval($_GET['annee_debut']) : START_YEAR;
    $annee_fin = isset($_GET['annee_fin']) ? intval($_GET['annee_fin']) : intval(CURRENT_YEAR);

    // Si limit est spécifié, ignorer les années et prendre les derniers mois
    if ($limit) {
        // Récupérer les derniers mois
        $historique = $calculator->getHistoriqueLimit($limit);
    } else {
        // Valider les paramètres
        if (!validerAnnee($annee_debut) || !validerAnnee($annee_fin)) {
            throw new Exception("Années non valides");
        }

        if ($annee_debut > $annee_fin) {
            throw new Exception("L'année de début doit être inférieure à l'année de fin");
        }

        // Récupérer les données
        $historique = $calculator->getHistorique($annee_debut, $annee_fin);
    }

    // Formater pour l'affichage
    $data = array_map(function($item) {
        return [
            'date' => $item['annee'] . '-' . str_pad($item['mois'], 2, '0', STR_PAD_LEFT),
            'annee' => intval($item['annee']),
            'mois' => intval($item['mois']),
            'mois_nom' => getMoisCourt($item['mois']),
            'ipc' => round(floatval($item['valeur_ipc']), 2),
            'inflation_mensuelle' => round(floatval($item['inflation_mensuelle']), 2),
            'inflation_annuelle' => round(floatval($item['inflation_annuelle']), 2),
            'inflation_sous_jacente' => round(floatval($item['inflation_sous_jacente'] ?? 0), 2)
        ];
    }, $historique);

    echo json_encode([
        'success' => true,
        'periode' => [
            'debut' => $annee_debut,
            'fin' => $annee_fin
        ],
        'count' => count($data),
        'data' => $data
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