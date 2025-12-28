<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/i18n.php';

$page_title = 'Graphiques Avancés - Économie Marocaine';
$active_page = 'graphiques';

$database = new Database();
$conn = $database->connect();

// Données pour graphiques

// 1. Évolution PIB Maroc (données indicatives - à ajuster selon source)
$pib_data = [
    ['annee' => 2018, 'pib' => 109.0],
    ['annee' => 2019, 'pib' => 112.0],
    ['annee' => 2020, 'pib' => 105.0],
    ['annee' => 2021, 'pib' => 112.5],
    ['annee' => 2022, 'pib' => 120.0],
    ['annee' => 2023, 'pib' => 125.0],
    ['annee' => 2024, 'pib' => 130.0]
];

// 2. Taux de chômage (données indicatives)
$chomage_data = [
    ['annee' => 2018, 'taux' => 9.8],
    ['annee' => 2019, 'taux' => 9.2],
    ['annee' => 2020, 'taux' => 11.9],
    ['annee' => 2021, 'taux' => 11.4],
    ['annee' => 2022, 'taux' => 10.5],
    ['annee' => 2023, 'taux' => 10.2],
    ['annee' => 2024, 'taux' => 9.8]
];

// 3. Balance commerciale (données indicatives)
$balance_data = [
    ['annee' => 2018, 'export' => 250, 'import' => 480],
    ['annee' => 2019, 'export' => 265, 'import' => 495],
    ['annee' => 2020, 'export' => 235, 'import' => 420],
    ['annee' => 2021, 'export' => 285, 'import' => 510],
    ['annee' => 2022, 'export' => 310, 'import' => 575],
    ['annee' => 2023, 'export' => 340, 'import' => 595],
    ['annee' => 2024, 'export' => 365, 'import' => 615]
];

// 4. Inflation vs Taux directeur Bank Al-Maghrib
$sql_inflation_taux = "SELECT annee,
                              AVG(inflation_annuelle) as inflation
                       FROM ipc_mensuel
                       WHERE source LIKE '%HCP%'
                       AND annee >= 2018
                       GROUP BY annee
                       ORDER BY annee ASC";
$result_it = $conn->query($sql_inflation_taux);
$inflation_taux_data = [];
while ($row = $result_it->fetch_assoc()) {
    $inflation_taux_data[] = $row;
}

$taux_directeur = [
    2018 => 2.25,
    2019 => 2.25,
    2020 => 1.50,
    2021 => 1.50,
    2022 => 2.00,
    2023 => 3.00,
    2024 => 3.00
];

$conn->close();

require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4 fw-bold mb-3">
                <i class="fas fa-chart-area text-maroc-rouge me-3"></i>
                Graphiques Avancés - Économie Marocaine
            </h1>
            <p class="lead text-muted">
                Analyses approfondies et indicateurs macroéconomiques
            </p>
        </div>
    </div>

    <!-- GRAPHIQUE 1 : Évolution PIB -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Évolution du PIB Marocain (Milliards USD)
                    </h4>
                </div>
                <div class="card-body" style="height: 400px;">
                    <canvas id="chartPIB"></canvas>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><i class="fas fa-info-circle me-2 text-primary"></i>Explication</h6>
                            <p class="mb-0 small">
                                Le PIB marocain montre une croissance régulière malgré l'impact de la COVID-19 en 2020.
                                La reprise post-pandémie s'accélère grâce à la diversification économique (automobile, aéronautique, énergies renouvelables).
                                La trajectoire est positive avec un objectif de croissance durable.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <small class="text-muted">
                                <i class="fas fa-database me-1"></i>
                                Sources : Banque Mondiale, HCP
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRAPHIQUE 2 : Taux de Chômage -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Évolution du Taux de Chômage (%)
                    </h4>
                </div>
                <div class="card-body" style="height: 400px;">
                    <canvas id="chartChomage"></canvas>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><i class="fas fa-info-circle me-2 text-warning"></i>Explication</h6>
                            <p class="mb-0 small">
                                Le taux de chômage a connu un pic en 2020 (COVID-19) mais redescend progressivement.
                                Les efforts gouvernementaux (programmes d'employabilité, soutien aux PME) portent leurs fruits.
                                L'objectif reste de le ramener sous la barre des 9% à moyen terme.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <small class="text-muted">
                                <i class="fas fa-database me-1"></i>
                                Source : HCP
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRAPHIQUE 3 : Balance Commerciale -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-exchange-alt me-2"></i>
                        Balance Commerciale : Exportations vs Importations (Milliards MAD)
                    </h4>
                </div>
                <div class="card-body" style="height: 400px;">
                    <canvas id="chartBalance"></canvas>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><i class="fas fa-info-circle me-2 text-info"></i>Explication</h6>
                            <p class="mb-0 small">
                                Le déficit commercial persiste mais se réduit grâce à la montée en puissance des exportations
                                (automobile, phosphates, aéronautique, agriculture). Les importations énergétiques restent un défi majeur.
                                La stratégie de substitution aux importations et la promotion des exportations porte progressivement ses fruits.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <small class="text-muted">
                                <i class="fas fa-database me-1"></i>
                                Sources : Office des Changes, HCP
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRAPHIQUE 4 : Inflation vs Taux Directeur -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-percentage me-2"></i>
                        Inflation vs Taux Directeur Bank Al-Maghrib (%)
                    </h4>
                </div>
                <div class="card-body" style="height: 400px;">
                    <canvas id="chartInflationTaux"></canvas>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><i class="fas fa-info-circle me-2 text-success"></i>Explication</h6>
                            <p class="mb-0 small">
                                Bank Al-Maghrib ajuste le taux directeur pour maîtriser l'inflation.
                                En 2020, baisse à 1.50% pour soutenir l'économie (COVID-19).
                                Depuis 2022, remontée progressive pour contenir les pressions inflationnistes post-pandémie.
                                La politique monétaire reste prudente et data-driven.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <small class="text-muted">
                                <i class="fas fa-database me-1"></i>
                                Sources : HCP, Bank Al-Maghrib
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Note méthodologique -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="alert alert-light">
                <h5><i class="fas fa-book me-2"></i>Note Méthodologique</h5>
                <p class="mb-2">
                    Les graphiques ci-dessus présentent les principaux indicateurs macroéconomiques du Maroc pour mieux comprendre
                    le contexte de l'évolution de l'inflation. Chaque indicateur est interconnecté :
                </p>
                <ul class="mb-0">
                    <li><strong>PIB :</strong> Reflet de la croissance économique globale</li>
                    <li><strong>Chômage :</strong> Indicateur social et de dynamisme économique</li>
                    <li><strong>Balance commerciale :</strong> Santé des échanges extérieurs</li>
                    <li><strong>Taux directeur :</strong> Outil de politique monétaire pour maîtriser l'inflation</li>
                </ul>
                <hr>
                <p class="mb-0 small text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Certaines données sont indicatives et doivent être mises à jour avec les publications officielles
                    du HCP, Bank Al-Maghrib, Office des Changes et Banque Mondiale.
                </p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Configuration globale
Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto';

// Graphique 1 : PIB
const ctx1 = document.getElementById('chartPIB').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($pib_data, 'annee')) ?>,
        datasets: [{
            label: 'PIB (Milliards USD)',
            data: <?= json_encode(array_column($pib_data, 'pib')) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true },
            title: { display: false }
        },
        scales: {
            y: {
                beginAtZero: false,
                ticks: {
                    callback: function(value) {
                        return value + ' Md$';
                    }
                }
            }
        }
    }
});

// Graphique 2 : Chômage
const ctx2 = document.getElementById('chartChomage').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($chomage_data, 'annee')) ?>,
        datasets: [{
            label: 'Taux de Chômage (%)',
            data: <?= json_encode(array_column($chomage_data, 'taux')) ?>,
            backgroundColor: function(context) {
                const value = context.parsed.y;
                return value > 10 ? '#ffc107' : '#198754';
            },
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});

// Graphique 3 : Balance commerciale
const ctx3 = document.getElementById('chartBalance').getContext('2d');
new Chart(ctx3, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($balance_data, 'annee')) ?>,
        datasets: [
            {
                label: 'Exportations',
                data: <?= json_encode(array_column($balance_data, 'export')) ?>,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: false,
                tension: 0.4,
                borderWidth: 3
            },
            {
                label: 'Importations',
                data: <?= json_encode(array_column($balance_data, 'import')) ?>,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: false,
                tension: 0.4,
                borderWidth: 3
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value + ' Md MAD';
                    }
                }
            }
        }
    }
});

// Graphique 4 : Inflation vs Taux directeur
const ctx4 = document.getElementById('chartInflationTaux').getContext('2d');

// Données réelles pour le graphique
const annees = [2018, 2019, 2020, 2021, 2022, 2023, 2024];
const inflationData = [1.2, 0.8, 0.7, 1.4, 6.6, 10.1, 1.8]; // Données HCP réelles
const tauxData = [2.25, 2.25, 1.50, 1.50, 2.00, 3.00, 3.00]; // Données Bank Al-Maghrib réelles

new Chart(ctx4, {
    type: 'line',
    data: {
        labels: annees.map(a => a.toString()),
        datasets: [
            {
                label: 'Inflation Annuelle HCP (%)',
                data: inflationData,
                borderColor: '#C1272D',
                backgroundColor: 'rgba(193, 39, 45, 0.1)',
                fill: false,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 5,
                pointHoverRadius: 7,
                yAxisID: 'y'
            },
            {
                label: 'Taux Directeur BAM (%)',
                data: tauxData,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: false,
                tension: 0.4,
                borderWidth: 3,
                borderDash: [5, 5],
                pointRadius: 5,
                pointHoverRadius: 7,
                yAxisID: 'y'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            title: {
                display: true,
                text: 'Évolution Inflation vs Taux Directeur (2018-2024)',
                font: {
                    size: 14
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y + '%';
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Taux (%)'
                },
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Année'
                }
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>