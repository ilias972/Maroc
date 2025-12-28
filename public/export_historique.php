<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/export.php';

$format = $_GET['format'] ?? 'pdf';
$annee_debut = $_GET['annee_debut'] ?? START_YEAR;
$annee_fin = $_GET['annee_fin'] ?? CURRENT_YEAR;

$database = new Database();
$conn = $database->connect();

// Récupérer les données
$sql = "SELECT annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle
        FROM ipc_mensuel
        WHERE annee BETWEEN ? AND ?
        ORDER BY annee DESC, mois DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $annee_debut, $annee_fin);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        getMoisNom($row['mois']) . ' ' . $row['annee'],
        number_format($row['valeur_ipc'], 2),
        number_format($row['inflation_mensuelle'], 2) . '%',
        number_format($row['inflation_annuelle'], 2) . '%'
    ];
}

$conn->close();

$headers = ['Date', 'IPC', 'Inflation Mensuelle', 'Inflation Annuelle'];
$title = 'Historique Inflation Maroc (' . $annee_debut . '-' . $annee_fin . ')';
$filename = 'inflation_maroc_' . $annee_debut . '_' . $annee_fin;

if ($format === 'pdf') {
    DataExporter::exportPDF($data, $title, $headers, $filename . '.pdf');
} elseif ($format === 'excel') {
    DataExporter::exportExcel($data, $title, $headers, $filename . '.xlsx');
}
?>