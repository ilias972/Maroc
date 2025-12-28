<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/export.php';

$format = $_GET['format'] ?? 'pdf';

$database = new Database();
$conn = $database->connect();

// Récupérer les comparaisons
$annee = CURRENT_YEAR;
$mois = 12;

$sql = "SELECT pays, inflation_annuelle
        FROM inflation_internationale
        WHERE annee = ? AND mois = ?
        ORDER BY inflation_annuelle DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $annee, $mois);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$rank = 1;
while ($row = $result->fetch_assoc()) {
    $data[] = [
        $rank++,
        $row['pays'],
        number_format($row['inflation_annuelle'], 2) . '%'
    ];
}

$conn->close();

$headers = ['Rang', 'Pays', 'Inflation'];
$title = 'Comparaisons Internationales - Décembre ' . $annee;
$filename = 'comparaisons_inflation_' . $annee;

if ($format === 'pdf') {
    DataExporter::exportPDF($data, $title, $headers, $filename . '.pdf');
} elseif ($format === 'excel') {
    DataExporter::exportExcel($data, $title, $headers, $filename . '.xlsx');
}
?>