<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/export.php';

$format = $_GET['format'] ?? 'pdf';

$database = new Database();
$conn = $database->connect();

// Récupérer données régionales
$sql = "SELECT
            d.ville, d.population, d.taux_chomage, d.taux_pauvrete, i.inflation_value
        FROM demographie_villes d
        LEFT JOIN ipc_villes i ON d.ville = i.ville AND i.annee = 2024 AND i.mois = 12
        ORDER BY d.population DESC";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $population = $row['population'] ? number_format($row['population']) : 'N/A';
    $chomage = $row['taux_chomage'] !== null ? number_format($row['taux_chomage'], 1) . '%' : 'N/A';
    $pauvrete = $row['taux_pauvrete'] !== null ? number_format($row['taux_pauvrete'], 1) . '%' : 'N/A';
    $inflation = $row['inflation_value'] !== null ? number_format($row['inflation_value'], 2) . '%' : 'N/A';

    $data[] = [
        $row['ville'],
        $population,
        $chomage,
        $pauvrete,
        $inflation
    ];
}

$conn->close();

$headers = ['Ville', 'Population', 'Chômage', 'Pauvreté', 'Inflation'];
$title = 'Données Régionales Maroc - Décembre 2024';
$filename = 'donnees_regionales_maroc';

if ($format === 'pdf') {
    DataExporter::exportPDF($data, $title, $headers, $filename . '.pdf');
} elseif ($format === 'excel') {
    DataExporter::exportExcel($data, $title, $headers, $filename . '.xlsx');
}
?>
