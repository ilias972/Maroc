<?php
/**
 * API - Calculer le pouvoir d'achat
 *
 * Paramètres GET:
 * - montant : montant initial en DH
 * - annee_depart : année de départ
 * - mois_depart : mois de départ
 * - annee_arrivee : année d'arrivée
 * - mois_arrivee : mois d'arrivée
 *
 * Retour: JSON avec le résultat du calcul
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

try {
    $database = new Database();
    $conn = $database->connect();
    $calculator = new InflationCalculator($conn);

    // Récupérer les paramètres (GET ou POST)
    $montant = isset($_REQUEST['montant']) ? floatval($_REQUEST['montant']) : 0;
    $annee_depart = isset($_REQUEST['annee_depart']) ? intval($_REQUEST['annee_depart']) : 0;
    $mois_depart = isset($_REQUEST['mois_depart']) ? intval($_REQUEST['mois_depart']) : 0;
    $annee_arrivee = isset($_REQUEST['annee_arrivee']) ? intval($_REQUEST['annee_arrivee']) : 0;
    $mois_arrivee = isset($_REQUEST['mois_arrivee']) ? intval($_REQUEST['mois_arrivee']) : 0;

    // Validation
    if (!validerMontant($montant)) {
        throw new Exception("Montant non valide");
    }

    if (!validerAnnee($annee_depart) || !validerMois($mois_depart)) {
        throw new Exception("Date de départ non valide");
    }

    if (!validerAnnee($annee_arrivee) || !validerMois($mois_arrivee)) {
        throw new Exception("Date d'arrivée non valide");
    }

    // Calculer
    $resultat = $calculator->calculerPouvoirAchat(
        $montant,
        $annee_depart,
        $mois_depart,
        $annee_arrivee,
        $mois_arrivee
    );

    if (isset($resultat['error'])) {
        throw new Exception($resultat['error']);
    }

    echo json_encode([
        'success' => true,
        'resultat' => $resultat
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>