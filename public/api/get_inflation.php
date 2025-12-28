<?php
/**
 * API - Récupérer l'inflation actuelle avec détails
 *
 * Paramètres GET:
 * - annee (optionnel) : année spécifique
 * - mois (optionnel) : mois spécifique
 *
 * Retour: JSON avec inflation + catégories
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

    // Paramètres
    $annee = isset($_GET['annee']) ? intval($_GET['annee']) : null;
    $mois = isset($_GET['mois']) ? intval($_GET['mois']) : null;

    // Si pas de paramètres, prendre le dernier mois
    if (!$annee || !$mois) {
        $inflation_actuelle = $calculator->getInflationActuelle();
        $annee = $inflation_actuelle['annee'];
        $mois = $inflation_actuelle['mois'];
    } else {
        // Valider les paramètres
        if (!validerAnnee($annee) || !validerMois($mois)) {
            throw new Exception("Date non valide");
        }

        // Récupérer les données pour cette date
        $sql = "SELECT * FROM ipc_mensuel WHERE annee = ? AND mois = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $annee, $mois);
        $stmt->execute();
        $result = $stmt->get_result();
        $inflation_actuelle = $result->fetch_assoc();

        if (!$inflation_actuelle) {
            throw new Exception("Aucune donnée pour cette période");
        }
    }

    // Récupérer les catégories
    $categories = $calculator->getInflationParCategorie($annee, $mois);

    // Formater les catégories
    $categories_formatted = array_map(function($cat) {
        return [
            'categorie' => $cat['categorie'],
            'nom' => getCategorieName($cat['categorie']),
            'inflation' => round(floatval($cat['inflation_value']), 2),
            'ponderation' => round(floatval($cat['ponderation']), 2)
        ];
    }, $categories);

    echo json_encode([
        'success' => true,
        'periode' => [
            'annee' => intval($annee),
            'mois' => intval($mois),
            'mois_nom' => getMoisNom($mois),
            'date' => $annee . '-' . str_pad($mois, 2, '0', STR_PAD_LEFT)
        ],
        'inflation' => [
            'ipc' => round(floatval($inflation_actuelle['valeur_ipc']), 2),
            'mensuelle' => round(floatval($inflation_actuelle['inflation_mensuelle']), 2),
            'annuelle' => round(floatval($inflation_actuelle['inflation_annuelle']), 2),
            'sous_jacente' => round(floatval($inflation_actuelle['inflation_sous_jacente'] ?? 0), 2)
        ],
        'categories' => $categories_formatted
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