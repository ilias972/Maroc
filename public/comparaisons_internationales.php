<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$page_title = 'Comparaisons Internationales';

$database = new Database();
$conn = $database->connect();

// DONNÉES RÉELLES : Dernière année disponible
$sql_annee = "SELECT MAX(annee) as derniere_annee
              FROM inflation_internationale
              WHERE source = 'World Bank API'";
$result = $conn->query($sql_annee);
$derniere_annee = $result->fetch_assoc()['derniere_annee'] ?? date('Y') - 1;

// Maroc (HCP)
$sql_mar = "SELECT inflation_annuelle FROM ipc_mensuel
            WHERE annee = ? AND mois = 12 AND source LIKE '%HCP%'
            LIMIT 1";
$stmt = $conn->prepare($sql_mar);
$stmt->bind_param('i', $derniere_annee);
$stmt->execute();
$maroc = $stmt->get_result()->fetch_assoc();

// 20 PAYS LES PLUS IMPORTANTS POUR LE MAROC
$pays_importants = [
    // G7
    'France', 'Allemagne', 'Italie', 'Royaume-Uni', 'États-Unis', 'Canada', 'Japon',
    // Maghreb & Afrique
    'Algérie', 'Tunisie', 'Égypte', 'Sénégal', 'Côte d\'Ivoire',
    // Europe proximité
    'Espagne', 'Portugal', 'Belgique', 'Pays-Bas',
    // Moyen-Orient
    'Arabie Saoudite', 'Émirats Arabes Unis', 'Qatar',
    // Asie
    'Chine', 'Inde'
];

// Pays internationaux (20 pays sélectionnés + Maroc)
$placeholders = str_repeat('?,', count($pays_importants) - 1) . '?';
$sql_int = "SELECT pays, code_pays, inflation_annuelle
            FROM inflation_internationale
            WHERE annee = ? AND mois = 12
            AND source = 'World Bank API'
            AND (pays IN ($placeholders) OR pays = 'Maroc')
            ORDER BY inflation_annuelle DESC";

$stmt = $conn->prepare($sql_int);
$params = array_merge([$derniere_annee], $pays_importants);
$types = 'i' . str_repeat('s', count($pays_importants));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$pays_data = [];
while ($row = $result->fetch_assoc()) {
    $pays_data[] = $row;
}

// Ajouter Maroc
if ($maroc && $maroc['inflation_annuelle']) {
    $pays_data[] = [
        'pays' => 'Maroc',
        'code_pays' => 'MAR',
        'inflation_annuelle' => $maroc['inflation_annuelle']
    ];
}

// Trier par inflation
usort($pays_data, function($a, $b) {
    return $b['inflation_annuelle'] <=> $a['inflation_annuelle'];
});

$annee = $derniere_annee;

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5 fade-in">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-globe-europe text-maroc-rouge me-3"></i>
            Comparaisons Internationales
        </h1>
        <p class="lead text-muted">
            Comparez l'inflation du Maroc avec d'autres pays
        </p>
    </div>

    <!-- Classement -->
    <div class="row mb-5">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow slide-in-left">
                <div class="card-header bg-maroc-rouge text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>
                        Classement par Inflation - Décembre <?= $annee ?>
                    </h4>
                    <div class="btn-group">
                        <a href="export_comparaisons.php?format=pdf" class="btn btn-sm btn-light">
                            <i class="fas fa-file-pdf me-2"></i>
                            PDF
                        </a>
                        <a href="export_comparaisons.php?format=excel" class="btn btn-sm btn-light">
                            <i class="fas fa-file-excel me-2"></i>
                            Excel
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">#</th>
                                    <th>Pays</th>
                                    <th class="text-end">Inflation</th>
                                    <th width="100"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pays_data as $index => $pays): ?>
                                <tr <?= $pays['pays'] === 'Maroc' ? 'class="table-success"' : '' ?>>
                                    <td class="text-center fw-bold"><?= $index + 1 ?></td>
                                    <td>
                                        <?php if ($pays['pays'] === 'Maroc'): ?>
                                            <i class="fas fa-star text-warning me-2"></i>
                                        <?php endif; ?>
                                        <strong><?= $pays['pays'] ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge <?= $pays['inflation_annuelle'] > 3 ? 'bg-danger' : ($pays['inflation_annuelle'] > 2 ? 'bg-warning' : 'bg-success') ?>">
                                            <?= formatPourcentage($pays['inflation_annuelle']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?= $pays['inflation_annuelle'] > 3 ? 'bg-danger' : 'bg-success' ?>"
                                                 style="width: <?= min(($pays['inflation_annuelle'] / 10) * 100, 100) ?>%">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique -->
    <div class="card shadow mb-5 fade-in">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-chart-bar me-2"></i>
                Graphique Comparatif
            </h4>
        </div>
        <div class="card-body p-4">
            <canvas id="comparaisonsChart" height="80"></canvas>
        </div>
    </div>

    <!-- Section ANALYSE CONTEXTUELLE -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Analyse Contextuelle de l'Inflation Marocaine
                    </h4>
                </div>
                <div class="card-body">
                    <?php
                    // Calculer inflation Maroc vs moyennes
                    $inflation_maroc = 0;
                    $inflation_g7 = [];
                    $inflation_maghreb = [];
                    $inflation_europe = [];

                    foreach ($pays_data as $pays) {
                        if ($pays['pays'] === 'Maroc') {
                            $inflation_maroc = $pays['inflation_annuelle'];
                        }

                        if (in_array($pays['pays'], ['France', 'Allemagne', 'Italie', 'Royaume-Uni', 'États-Unis', 'Canada', 'Japon'])) {
                            $inflation_g7[] = $pays['inflation_annuelle'];
                        }

                        if (in_array($pays['pays'], ['Algérie', 'Tunisie', 'Égypte'])) {
                            $inflation_maghreb[] = $pays['inflation_annuelle'];
                        }

                        if (in_array($pays['pays'], ['France', 'Espagne', 'Portugal', 'Allemagne', 'Italie'])) {
                            $inflation_europe[] = $pays['inflation_annuelle'];
                        }
                    }

                    $moy_g7 = !empty($inflation_g7) ? array_sum($inflation_g7) / count($inflation_g7) : 0;
                    $moy_maghreb = !empty($inflation_maghreb) ? array_sum($inflation_maghreb) / count($inflation_maghreb) : 0;
                    $moy_europe = !empty($inflation_europe) ? array_sum($inflation_europe) / count($inflation_europe) : 0;

                    // Position du Maroc
                    $position_maroc = 0;
                    foreach ($pays_data as $index => $pays) {
                        if ($pays['pays'] === 'Maroc') {
                            $position_maroc = $index + 1;
                            break;
                        }
                    }
                    ?>

                    <h5 class="mb-3">Situation du Maroc (<?= $derniere_annee ?>)</h5>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="alert alert-primary">
                                <h6><i class="fas fa-flag me-2"></i>Inflation Maroc</h6>
                                <h3><?= number_format($inflation_maroc, 2) ?>%</h3>
                                <p class="mb-0 small">
                                    Position : <?= $position_maroc ?>/<?= count($pays_data) ?> pays
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="alert alert-info">
                                <h6><i class="fas fa-globe me-2"></i>Moyenne G7</h6>
                                <h3><?= number_format($moy_g7, 2) ?>%</h3>
                                <p class="mb-0 small">
                                    <?php if ($inflation_maroc < $moy_g7): ?>
                                        <span class="text-success">
                                            <i class="fas fa-arrow-down"></i>
                                            <?= number_format($moy_g7 - $inflation_maroc, 2) ?>% en dessous
                                        </span>
                                    <?php else: ?>
                                        <span class="text-danger">
                                            <i class="fas fa-arrow-up"></i>
                                            <?= number_format($inflation_maroc - $moy_g7, 2) ?>% au-dessus
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-map me-2"></i>Moyenne Maghreb</h6>
                                <h3><?= number_format($moy_maghreb, 2) ?>%</h3>
                                <p class="mb-0 small">
                                    <?php if ($inflation_maroc < $moy_maghreb): ?>
                                        <span class="text-success">
                                            <i class="fas fa-arrow-down"></i>
                                            <?= number_format($moy_maghreb - $inflation_maroc, 2) ?>% en dessous
                                        </span>
                                    <?php else: ?>
                                        <span class="text-danger">
                                            <i class="fas fa-arrow-up"></i>
                                            <?= number_format($inflation_maroc - $moy_maghreb, 2) ?>% au-dessus
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Facteurs Explicatifs</h6>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success"><i class="fas fa-check-circle me-2"></i>Points Positifs</h6>
                            <ul>
                                <li>Politique monétaire prudente de Bank Al-Maghrib</li>
                                <li>Résilience de l'économie marocaine</li>
                                <li>Diversification sectorielle progressive</li>
                                <?php if ($inflation_maroc < $moy_g7): ?>
                                    <li>Inflation plus maîtrisée que les économies avancées</li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Défis</h6>
                            <ul>
                                <li>Impact des cours internationaux des matières premières</li>
                                <li>Dépendance énergétique (pétrole, gaz)</li>
                                <li>Volatilité des prix alimentaires (sécheresse)</li>
                                <li>Effets de la conjoncture internationale</li>
                            </ul>
                        </div>
                    </div>

                    <div class="alert alert-light mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note méthodologique :</strong>
                        Les comparaisons portent sur <?= count($pays_data) ?> pays sélectionnés selon leur importance
                        stratégique pour le Maroc (proximité géographique, partenaires commerciaux, G7, Maghreb).
                        Sources : World Bank, HCP, Bank Al-Maghrib.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sources des Données -->
<div class="mt-4 p-3 bg-light rounded">
    <h6 class="mb-2">
        <i class="fas fa-info-circle me-2"></i>
        Sources des Données
    </h6>
    <ul class="list-unstyled small mb-0">
        <li>
            <i class="fas fa-check text-success me-2"></i>
            <strong>IPC & Inflation :</strong> Haut-Commissariat au Plan (HCP) via data.gov.ma
        </li>
        <li>
            <i class="fas fa-check text-success me-2"></i>
            <strong>Taux de Change :</strong> Bank Al-Maghrib (API officielle)
        </li>
        <li>
            <i class="fas fa-check text-success me-2"></i>
            <strong>Comparaisons Internationales :</strong> World Bank Open Data
        </li>
        <li class="text-muted mt-2">
            <i class="fas fa-sync me-1"></i>
            Dernière synchronisation : <?= date('d/m/Y H:i') ?>
        </li>
    </ul>
</div>

<script>
const paysData = <?= json_encode($pays_data) ?>;

const ctx = document.getElementById('comparaisonsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: paysData.map(p => p.pays),
        datasets: [{
            label: 'Inflation (%)',
            data: paysData.map(p => parseFloat(p.inflation_annuelle)),
            backgroundColor: paysData.map(p =>
                p.pays === 'Maroc' ? 'rgba(193, 39, 45, 0.8)' : 'rgba(13, 110, 253, 0.6)'
            ),
            borderColor: paysData.map(p =>
                p.pays === 'Maroc' ? 'rgb(193, 39, 45)' : 'rgb(13, 110, 253)'
            ),
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'Comparaison Internationale (Source: World Bank)'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => value + '%'
                }
            }
        }
    }
});
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>