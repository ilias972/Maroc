<?php
/**
 * API - Récupérer les statistiques générales d'inflation
 *
 * Retour: JSON avec les statistiques calculées
 */

require_once '../../includes/config.php';

// CORRECTION CORS : N'autoriser que l'origine de l'application (pas d'étoile *)
$allowed_origin = rtrim(SITE_URL, '/');
if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowed_origin) {
    header("Access-Control-Allow-Origin: " . $allowed_origin);
}
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');

require_once '../../includes/database.php';
require_once '../../includes/functions.php';

try {
    $database = new Database();
    $conn = $database->connect();

    // Statistiques générales depuis START_YEAR
    $sql = "SELECT
                AVG(inflation_annuelle) as moyenne,
                MAX(inflation_annuelle) as max,
                MIN(inflation_annuelle) as min,
                COUNT(*) as nb_mois,
                MAX(annee) as annee_max,
                MIN(annee) as annee_min
            FROM ipc_mensuel
            WHERE annee >= ? AND source LIKE '%HCP%'";

    $stmt = $conn->prepare($sql);
    $start_year = START_YEAR;
    $stmt->bind_param('i', $start_year);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();

    // Statistiques par période récente (5 dernières années)
    $recent_year = CURRENT_YEAR - 5;
    $sql_recent = "SELECT
                        AVG(inflation_annuelle) as moyenne_recent,
                        MAX(inflation_annuelle) as max_recent,
                        MIN(inflation_annuelle) as min_recent
                    FROM ipc_mensuel
                    WHERE annee >= ? AND source LIKE '%HCP%'";

    $stmt_recent = $conn->prepare($sql_recent);
    $stmt_recent->bind_param('i', $recent_year);
    $stmt_recent->execute();
    $result_recent = $stmt_recent->get_result();
    $stats_recent = $result_recent->fetch_assoc();

    // Tendance actuelle (comparaison avec l'année précédente)
    $current_year = CURRENT_YEAR;
    $previous_year = CURRENT_YEAR - 1;

    $sql_trend = "SELECT
                        AVG(CASE WHEN annee = ? THEN inflation_annuelle END) as current_year_avg,
                        AVG(CASE WHEN annee = ? THEN inflation_annuelle END) as previous_year_avg
                    FROM ipc_mensuel
                    WHERE annee IN (?, ?) AND source LIKE '%HCP%'";

    $stmt_trend = $conn->prepare($sql_trend);
    $stmt_trend->bind_param('iiii', $current_year, $previous_year, $current_year, $previous_year);
    $stmt_trend->execute();
    $result_trend = $stmt_trend->get_result();
    $trend = $result_trend->fetch_assoc();

    echo json_encode([
        'success' => true,
        'stats' => [
            'periode_complete' => [
                'annees' => intval($stats['annee_max'] - $stats['annee_min'] + 1),
                'mois' => intval($stats['nb_mois']),
                'moyenne' => round(floatval($stats['moyenne']), 2),
                'maximum' => round(floatval($stats['max']), 2),
                'minimum' => round(floatval($stats['min']), 2)
            ],
            'periode_recente' => [
                'annees' => 5,
                'moyenne' => round(floatval($stats_recent['moyenne_recent']), 2),
                'maximum' => round(floatval($stats_recent['max_recent']), 2),
                'minimum' => round(floatval($stats_recent['min_recent']), 2)
            ],
            'tendance' => [
                'annee_courante' => round(floatval($trend['current_year_avg']), 2),
                'annee_precedente' => round(floatval($trend['previous_year_avg']), 2),
                'evolution' => round(floatval($trend['current_year_avg'] - $trend['previous_year_avg']), 2)
            ]
        ],
        'source' => 'HCP - Haut-Commissariat au Plan',
        'derniere_maj' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    
    // CORRECTION : Enregistrement de l'erreur côté serveur pour le débogage (ne pas exposer au client)
    error_log("Erreur API get_stats.php : " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur interne est survenue lors de la récupération des données.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
