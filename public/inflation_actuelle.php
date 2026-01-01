<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/i18n.php';

$page_title = __('current.title');

// Récupération des données réelles depuis la base de données
$database = new Database();
$conn = $database->connect();

// Dernier IPC HCP (inflation actuelle)
$sql_ipc = "SELECT annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle
            FROM ipc_mensuel
            ORDER BY annee DESC, mois DESC
            LIMIT 1";
$result_ipc = $conn->query($sql_ipc);
$dernierIPC = $result_ipc->fetch_assoc();

$inflation_actuelle = [
    'annee' => $dernierIPC['annee'] ?? date('Y'),
    'mois' => $dernierIPC['mois'] ?? date('n'),
    'valeur_ipc' => $dernierIPC['valeur_ipc'] ?? 100,
    'inflation_mensuelle' => $dernierIPC['inflation_mensuelle'] ?? 0,
    'inflation_annuelle' => $dernierIPC['inflation_annuelle'] ?? 0,
    'inflation_sous_jacente' => 0 // Sera calculé ci-dessous
];

// Inflation sous-jacente (moyenne annuelle)
$annee_actuelle = $inflation_actuelle['annee'];
$mois_actuel = $inflation_actuelle['mois'];

$sql_core = "SELECT AVG(inflation_annuelle) as core_inflation
            FROM ipc_mensuel
            WHERE annee = ? AND mois <= ?";
$stmt = $conn->prepare($sql_core);
$stmt->bind_param('ii', $annee_actuelle, $mois_actuel);
$stmt->execute();
$core_result = $stmt->get_result();
$core_data = $core_result->fetch_assoc();
$inflation_actuelle['inflation_sous_jacente'] = $core_data['core_inflation'] ?? 0;

// 12 derniers mois pour le graphique
$sql_12mois = "SELECT annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle
               FROM ipc_mensuel
               ORDER BY annee DESC, mois DESC
               LIMIT 12";
$result_12mois = $conn->query($sql_12mois);
$derniers_mois = [];
while ($row = $result_12mois->fetch_assoc()) {
    $derniers_mois[] = $row;
}
$derniers_mois = array_reverse($derniers_mois); // Chronologique

// Catégories d'inflation (dernier mois disponible)
$calculator = new InflationCalculator($conn);
$categories = $calculator->getInflationParCategorie($annee_actuelle, $mois_actuel);

$conn->close();

include '../includes/header.php';
?>

<div class="container py-5">
    <!-- En-tête -->
    <div class="text-center mb-5 fade-in">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-chart-line text-maroc-rouge me-3"></i>
            <?= __('current.title') ?>
        </h1>
        <p class="lead text-muted">
            <?= __('current.subtitle') ?> - Dernière mise à jour :
            <strong><?= getMoisNom($inflation_actuelle['mois']) ?> <?= $inflation_actuelle['annee'] ?></strong>
        </p>
    </div>

    <!-- Cartes principales -->
    <div class="row g-4 mb-5">
        <!-- Inflation Annuelle -->
        <div class="col-md-4">
            <div class="card text-center shadow-lg border-danger slide-in-left">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <i class="fas fa-calendar-alt fa-3x text-danger"></i>
                    </div>
                    <h6 class="text-muted mb-2"><?= __('home.current_inflation') ?></h6>
                    <h1 class="display-2 fw-bold text-danger mb-2">
                        <?= formatPourcentage($inflation_actuelle['inflation_annuelle']) ?>
                    </h1>
                    <p class="text-muted mb-0">Glissement annuel (12 mois)</p>
                </div>
            </div>
        </div>

        <!-- Inflation Mensuelle -->
        <div class="col-md-4">
            <div class="card text-center shadow-lg border-primary fade-in">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <i class="fas fa-chart-bar fa-3x text-primary"></i>
                    </div>
                    <h6 class="text-muted mb-2"><?= __('home.monthly_inflation') ?></h6>
                    <h1 class="display-2 fw-bold text-primary mb-2">
                        <?= formatPourcentage($inflation_actuelle['inflation_mensuelle']) ?>
                    </h1>
                    <p class="text-muted mb-0">Variation sur 1 mois</p>
                </div>
            </div>
        </div>

        <!-- Inflation Sous-jacente -->
        <div class="col-md-4">
            <div class="card text-center shadow-lg border-warning slide-in-right">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <i class="fas fa-filter fa-3x text-warning"></i>
                    </div>
                    <h6 class="text-muted mb-2"><?= __('home.core_inflation') ?></h6>
                    <h1 class="display-2 fw-bold text-warning mb-2">
                        <?= formatPourcentage($inflation_actuelle['inflation_sous_jacente'] ?? 0) ?>
                    </h1>
                    <p class="text-muted mb-0">Hors prix volatils</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Évolution des 12 derniers mois -->
    <div class="card shadow mb-5 fade-in">
        <div class="card-header bg-maroc-rouge text-white">
            <h4 class="mb-0">
                <i class="fas fa-chart-line me-2"></i>
                <?= __('history.evolution') ?> (12 mois)
            </h4>
        </div>
        <div class="card-body p-4">
            <canvas id="evolutionChart" height="80"></canvas>
        </div>
    </div>

    <!-- Inflation par Catégorie -->
    <div class="card shadow mb-5 slide-in-left">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-layer-group me-2"></i>
                <?= __('current.by_category') ?> - <?= getMoisNom($inflation_actuelle['mois']) ?> <?= $inflation_actuelle['annee'] ?>
            </h4>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <canvas id="categoriesChart"></canvas>
                </div>
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= __('common.category') ?></th>
                                    <th class="text-end"><?= __('common.inflation') ?></th>
                                    <th class="text-end">Poids</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-circle me-2"
                                           style="color: <?= $cat['inflation_value'] >= 0 ? '#dc3545' : '#198754' ?>"></i>
                                        <?= getCategorieName($cat['categorie']) ?>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge <?= $cat['inflation_value'] >= 0 ? 'bg-danger' : 'bg-success' ?>">
                                            <?= formatPourcentage($cat['inflation_value']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end text-muted">
                                        <?= number_format($cat['ponderation'], 1) ?>%
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

    <!-- Explications -->
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow slide-in-right">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Comprendre ces indicateurs
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">
                                <i class="fas fa-calendar-alt text-danger me-2"></i>
                                Inflation Annuelle
                            </h6>
                            <p class="text-muted mb-0">
                                Variation des prix sur 12 mois (glissement annuel).
                                Compare le mois actuel au même mois de l'année précédente.
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">
                                <i class="fas fa-chart-bar text-primary me-2"></i>
                                Inflation Mensuelle
                            </h6>
                            <p class="text-muted mb-0">
                                Variation des prix par rapport au mois précédent.
                                Indique la tendance immédiate de l'inflation.
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">
                                <i class="fas fa-filter text-warning me-2"></i>
                                Inflation Sous-jacente
                            </h6>
                            <p class="text-muted mb-0">
                                Exclut les produits à prix volatils (énergie, alimentation fraîche)
                                et les tarifs publics. Reflète la tendance de fond.
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="fw-bold">
                                <i class="fas fa-layer-group text-success me-2"></i>
                                Catégories
                            </h6>
                            <p class="text-muted mb-0">
                                Décomposition de l'inflation par type de produits avec
                                pondération selon leur poids dans le budget des ménages.
                            </p>
                        </div>
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
            <strong>Taux de Change :</strong> ExchangeRate-API (temps réel)
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
// Variables globales pour les données
let inflationData = null;
let categoriesData = [];
let evolutionChart = null;
let categoriesChart = null;

// Fonction pour charger les données depuis l'API
async function loadInflationData() {
    try {
        const response = await fetch('api/get_inflation.php');
        const data = await response.json();

        if (data.success) {
            inflationData = data.inflation;
            categoriesData = data.categories || [];

            // Mettre à jour l'affichage
            updateInflationDisplay(data);
            updateCharts(data);
        } else {
            console.error('Erreur API:', data.error);
            showError('Erreur lors du chargement des données');
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
        showError('Erreur de connexion au serveur');
    }
}

// Fonction pour mettre à jour l'affichage des chiffres
function updateInflationDisplay(data) {
    // Mettre à jour l'en-tête
    const headerElement = document.querySelector('.text-center.mb-5.fade-in p strong');
    if (headerElement) {
        headerElement.textContent = `${data.periode.mois_nom} ${data.periode.annee}`;
    }

    // Mettre à jour les cartes principales
    const cards = document.querySelectorAll('.card .display-2');
    if (cards.length >= 3) {
        // Inflation annuelle
        cards[0].textContent = data.inflation.annuelle.toFixed(1) + '%';
        // Inflation mensuelle
        cards[1].textContent = data.inflation.mensuelle.toFixed(1) + '%';
        // Inflation sous-jacente
        cards[2].textContent = (data.inflation.sous_jacente || 0).toFixed(1) + '%';
    }

    // Mettre à jour le titre des catégories
    const categoryTitle = document.querySelector('.card-header h4');
    if (categoryTitle && categoryTitle.textContent.includes('Inflation par Catégorie')) {
        categoryTitle.innerHTML = `<i class="fas fa-layer-group me-2"></i> Inflation par Catégorie - ${data.periode.mois_nom} ${data.periode.annee}`;
    }
}

// Fonction pour mettre à jour les graphiques
function updateCharts(data) {
    // Pour l'évolution, nous avons besoin des 12 derniers mois
    // Appelons une API séparée ou utilisons les données disponibles
    loadEvolutionData();
    updateCategoriesChart();
}

// Fonction pour charger les données d'évolution (12 derniers mois)
async function loadEvolutionData() {
    try {
        // Pour l'instant, utilisons une approche simplifiée
        // En production, créer une API dédiée pour les 12 derniers mois
        const response = await fetch('api/get_ipc.php?limit=12');
        const data = await response.json();

        if (data.success && data.data) {
            const derniersMois = data.data.reverse(); // Plus récent en dernier

            if (evolutionChart) {
                evolutionChart.destroy();
            }

            const ctxEvolution = document.getElementById('evolutionChart');
            if (ctxEvolution) {
                evolutionChart = new Chart(ctxEvolution, {
                    type: 'line',
                    data: {
                        labels: derniersMois.map(m => getMoisNom(m.mois) + ' ' + m.annee),
                        datasets: [
                            {
                                label: 'Inflation Annuelle (%)',
                                data: derniersMois.map(m => parseFloat(m.inflation_annuelle)),
                                borderColor: 'rgb(220, 53, 69)',
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6
                            },
                            {
                                label: 'Inflation Mensuelle (%)',
                                data: derniersMois.map(m => parseFloat(m.inflation_mensuelle)),
                                borderColor: 'rgb(13, 110, 253)',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 3,
                                pointHoverRadius: 5
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { position: 'top' },
                            title: {
                                display: true,
                                text: 'Évolution 12 derniers mois (Source: HCP)'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
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
            }
        }
    } catch (error) {
        console.error('Erreur chargement évolution:', error);
    }
}

// Fonction pour mettre à jour le graphique des catégories
function updateCategoriesChart() {
    if (categoriesChart) {
        categoriesChart.destroy();
    }

    const ctxCategories = document.getElementById('categoriesChart');
    if (ctxCategories && categoriesData.length > 0) {
        categoriesChart = new Chart(ctxCategories, {
            type: 'doughnut',
            data: {
                labels: categoriesData.map(c => c.nom || c.categorie.replace('_', ' ')),
                datasets: [{
                    data: categoriesData.map(c => parseFloat(c.ponderation)),
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(13, 110, 253, 0.8)',
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(13, 202, 240, 0.8)',
                        'rgba(111, 66, 193, 0.8)',
                        'rgba(253, 126, 20, 0.8)',
                        'rgba(214, 51, 132, 0.8)',
                        'rgba(32, 201, 151, 0.8)',
                        'rgba(102, 16, 242, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' },
                    title: {
                        display: true,
                        text: 'Pondération du panier IPC'
                    },
                    tooltip: {
                        callbacks: {
                            label: context => context.label + ': ' + context.parsed + '%'
                        }
                    }
                }
            }
        });

        // Mettre à jour le tableau des catégories
        updateCategoriesTable();
    }
}

// Fonction pour mettre à jour le tableau des catégories
function updateCategoriesTable() {
    const tbody = document.querySelector('#categoriesChart').closest('.row').querySelector('tbody');
    if (tbody && categoriesData.length > 0) {
        tbody.innerHTML = categoriesData.map(cat => `
            <tr>
                <td>
                    <i class="fas fa-circle me-2" style="color: ${cat.inflation >= 0 ? '#dc3545' : '#198754'}"></i>
                    ${cat.nom || cat.categorie.replace('_', ' ')}
                </td>
                <td class="text-end">
                    <span class="badge ${cat.inflation >= 0 ? 'bg-danger' : 'bg-success'}">
                        ${cat.inflation.toFixed(1)}%
                    </span>
                </td>
                <td class="text-end text-muted">
                    ${cat.ponderation.toFixed(1)}%
                </td>
            </tr>
        `).join('');
    }
}

// Fonction pour afficher les erreurs
function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
}

// Fonction pour formater les mois (côté JS)
function getMoisNom(mois) {
    const noms = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
                  'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    return noms[parseInt(mois)];
}

// Charger les données au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadInflationData();
});
</script>

<?php
include '../includes/footer.php';
?>