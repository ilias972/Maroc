<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/i18n.php';

$page_title = __('menu.home');

// =========================================================================
// RÉCUPÉRATION CENTRALISÉE DES DONNÉES (Performances DB optimisées)
// =========================================================================
$database = new Database();
$conn = $database->connect();
$calculator = new InflationCalculator($conn);

// 1. Dernier IPC HCP (inflation actuelle)
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
    'inflation_sous_jacente' => 0
];

// 2. Inflation sous-jacente (moyenne annuelle)
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

// 3. Catégories d'inflation
$categories = $calculator->getInflationParCategorie($annee_actuelle, $mois_actuel);

// 4. Statistiques globales
$stats = $calculator->getStatistiques();

// 5. Taux de change - 4 devises principales
$devises_principales = ['EUR', 'USD', 'GBP', 'CHF'];
$taux_devises = [];
$sql_taux = "SELECT cours_mad, date_taux FROM taux_change
             WHERE devise = ? AND type_taux = 'VIREMENT'
             ORDER BY date_taux DESC LIMIT 1";
$stmt_taux = $conn->prepare($sql_taux);

foreach ($devises_principales as $devise) {
    $stmt_taux->bind_param('s', $devise);
    $stmt_taux->execute();
    $result_taux = $stmt_taux->get_result();

    if ($result_taux->num_rows > 0) {
        $data = $result_taux->fetch_assoc();
        $jours_ecart = (strtotime(date('Y-m-d')) - strtotime($data['date_taux'])) / 86400;

        $taux_devises[$devise] = [
            'cours' => $data['cours_mad'],
            'date' => $data['date_taux'],
            'jours_ecart' => $jours_ecart
        ];
    } else {
        $taux_devises[$devise] = null;
    }
}

// 6. 12 Derniers mois d'inflation (pour le graphique)
$sql_12mois = "SELECT annee, mois, inflation_annuelle
               FROM ipc_mensuel
               ORDER BY annee DESC, mois DESC
               LIMIT 12";
$result_12mois = $conn->query($sql_12mois);
$donnees_12mois = [];
while ($row = $result_12mois->fetch_assoc()) {
    $donnees_12mois[] = $row;
}
$donnees_12mois = array_reverse($donnees_12mois); // Ordre chronologique

// 7. Statistiques Historiques
$sql_stats_histo = "SELECT
    MIN(annee) as annee_debut,
    MAX(annee) as annee_fin,
    COUNT(*) as nb_mois,
    AVG(inflation_annuelle) as inflation_moyenne,
    MAX(inflation_annuelle) as inflation_max,
    MIN(inflation_annuelle) as inflation_min,
    MIN(valeur_ipc) as ipc_min,
    MAX(valeur_ipc) as ipc_max
  FROM ipc_mensuel";
$stats_histo = $conn->query($sql_stats_histo)->fetch_assoc();

// 8. Inflation cumulée
$sql_cumul = "SELECT
    (SELECT valeur_ipc FROM ipc_mensuel ORDER BY annee ASC, mois ASC LIMIT 1) as ipc_debut,
    (SELECT valeur_ipc FROM ipc_mensuel ORDER BY annee DESC, mois DESC LIMIT 1) as ipc_fin";
$cumul = $conn->query($sql_cumul)->fetch_assoc();

$inflation_cumulee = 0;
if ($cumul['ipc_debut'] && $cumul['ipc_fin']) {
    $inflation_cumulee = (($cumul['ipc_fin'] - $cumul['ipc_debut']) / $cumul['ipc_debut']) * 100;
}

// Fermeture de la connexion BD (Unique et globale)
$conn->close();

// =========================================================================
// AFFICHAGE HTML
// =========================================================================
include '../includes/header.php';
?>

<script>
// Charger les données depuis les APIs
let pageData = {
    inflation: null,
    taux_change: null,
    categories: [],
    stats: null
};

// ... (Les fonctions JavaScript loadInflationData, loadExchangeRates, updateInflationDisplay etc. restent inchangées) ...

async function loadInflationData() {
    try {
        const response = await fetch('api/get_inflation.php');
        const data = await response.json();
        if (data.success) {
            pageData.inflation = data.inflation;
            pageData.categories = data.categories || [];
            updateInflationDisplay(data);
        }
    } catch (error) { console.error('Erreur chargement inflation:', error); }
}

async function loadExchangeRates() {
    try {
        const response = await fetch('api/get_exchange_rates.php');
        const data = await response.json();
        if (data.success) {
            pageData.taux_change = data.taux;
            updateExchangeRatesDisplay(data);
        }
    } catch (error) { console.error('Erreur chargement taux:', error); }
}

async function loadStats() {
    try {
        const response = await fetch('api/get_stats.php');
        const data = await response.json();
        if (data.success) {
            pageData.stats = data.stats;
            updateStatsDisplay(data);
        }
    } catch (error) { console.error('Erreur chargement stats:', error); }
}

function updateInflationDisplay(data) {
    const inflationCard = document.querySelector('.card.bg-white.text-dark h1.display-2');
    if (inflationCard) inflationCard.textContent = data.inflation.annuelle.toFixed(1) + '%';

    const dateElement = document.querySelector('.card.bg-white.text-dark p strong');
    if (dateElement) dateElement.textContent = data.periode.mois_nom + ' ' + data.periode.annee;

    const indicators = document.querySelectorAll('.card .display-5');
    if (indicators.length >= 3) {
        indicators[0].textContent = data.inflation.mensuelle.toFixed(1) + '%';
        indicators[1].textContent = (data.inflation.sous_jacente || 0).toFixed(1) + '%';
        indicators[2].textContent = data.inflation.ipc.toFixed(2);
    }
}

function updateExchangeRatesDisplay(data) {
    // Reste identique
}
function updateStatsDisplay(data) {
    // Reste identique
}
function updateCategoriesChart() {
    // Reste identique
}
function updateCategoriesTable() {
    // Reste identique
}

document.addEventListener('DOMContentLoaded', function() {
    loadInflationData();
    loadExchangeRates();
    loadStats();
    setTimeout(() => { updateCategoriesChart(); }, 1000);
});
</script>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0 slide-in-left">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-chart-line me-3" aria-hidden="true"></i>
                    <?= escapeHTML(__('home.hero_title')) ?>
                </h1>
                <p class="lead mb-4">
                    <?= escapeHTML(trans('home.hero_subtitle', ['year' => START_YEAR])) ?>
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="calculateur_inflation.php" class="btn btn-light btn-lg">
                        <i class="fas fa-calculator me-2" aria-hidden="true"></i>
                        <?= escapeHTML(__('menu.calculator')) ?>
                    </a>
                    <a href="inflation_historique.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-history me-2" aria-hidden="true"></i>
                        <?= escapeHTML(__('menu.history')) ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-4 slide-in-right">
                <div class="card bg-white text-dark shadow-lg">
                    <div class="card-body text-center p-4">
                        <h6 class="text-muted mb-2"><?= escapeHTML(__('home.current_inflation')) ?></h6>
                        <h1 class="display-2 fw-bold text-maroc-rouge mb-2">
                            <?= escapeHTML(formatPourcentage($inflation_actuelle['inflation_annuelle'])) ?>
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar-alt me-2" aria-hidden="true"></i>
                            <?= escapeHTML(getMoisNom($inflation_actuelle['mois'])) ?> <?= escapeHTML($inflation_actuelle['annee']) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-calendar-day fa-3x text-primary" aria-hidden="true"></i>
                        </div>
                        <h5 class="card-title"><?= escapeHTML(__('home.monthly_inflation')) ?></h5>
                        <h2 class="display-5 fw-bold text-primary mb-2">
                            <?= escapeHTML(formatPourcentage($inflation_actuelle['inflation_mensuelle'])) ?>
                        </h2>
                        <p class="text-muted">Variation sur le dernier mois</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-filter fa-3x text-warning" aria-hidden="true"></i>
                        </div>
                        <h5 class="card-title"><?= escapeHTML(__('home.core_inflation')) ?></h5>
                        <h2 class="display-5 fw-bold text-warning mb-2">
                            <?= escapeHTML(formatPourcentage($inflation_actuelle['inflation_sous_jacente'] ?? 0)) ?>
                        </h2>
                        <p class="text-muted">Hors prix volatils et tarifs publics</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-balance-scale fa-3x text-success" aria-hidden="true"></i>
                        </div>
                        <h5 class="card-title"><?= escapeHTML(__('home.ipc_index')) ?></h5>
                        <h2 class="display-5 fw-bold text-success mb-2">
                            <?= escapeHTML(number_format($inflation_actuelle['valeur_ipc'], 2)) ?>
                        </h2>
                        <p class="text-muted">Base <?= escapeHTML(BASE_YEAR) ?> = 100</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row mb-5">
    <div class="col-12 mt-4 container">
        <h3 class="mb-4">
            <i class="fas fa-exchange-alt me-2 text-maroc-rouge" aria-hidden="true"></i>
            Taux de Change Officiels
        </h3>
        <p class="text-muted mb-4">
            Source : Bank Al-Maghrib |
            Mise à jour : <?= escapeHTML(date('d/m/Y')) ?>
            <?php
            $dayOfWeek = date('N');
            if ($dayOfWeek >= 6) {
                echo '<span class="badge bg-warning">Marché fermé (week-end)</span>';
            }
            ?>
        </p>
    </div>

    <div class="container row mx-auto">
        <?php
        $devises_info = [
            'EUR' => ['nom' => 'Euro', 'icone' => 'fa-euro-sign', 'couleur' => 'primary'],
            'USD' => ['nom' => 'Dollar US', 'icone' => 'fa-dollar-sign', 'couleur' => 'success'],
            'GBP' => ['nom' => 'Livre Sterling', 'icone' => 'fa-pound-sign', 'couleur' => 'danger'],
            'CHF' => ['nom' => 'Franc Suisse', 'icone' => 'fa-coins', 'couleur' => 'warning']
        ];

        foreach ($devises_principales as $devise):
            $info = $devises_info[$devise];
            $taux = $taux_devises[$devise];
        ?>
        <div class="col-md-3 mb-3">
            <div class="card h-100 border-<?= escapeHTML($info['couleur']) ?>">
                <div class="card-body text-center">
                    <i class="fas <?= escapeHTML($info['icone']) ?> fa-3x text-<?= escapeHTML($info['couleur']) ?> mb-3" aria-hidden="true"></i>

                    <h6 class="text-muted mb-2"><?= escapeHTML($info['nom']) ?> (<?= escapeHTML($devise) ?>)</h6>

                    <?php if ($taux): ?>
                        <h3 class="text-<?= escapeHTML($info['couleur']) ?> mb-2">
                            <?= escapeHTML(number_format($taux['cours'], 4)) ?>
                        </h3>
                        <small class="text-muted">MAD</small>

                        <div class="mt-3">
                            <?php if ($taux['jours_ecart'] < 1): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check" aria-hidden="true"></i> Aujourd'hui
                                </span>
                            <?php elseif ($taux['jours_ecart'] <= 3): ?>
                                <span class="badge bg-warning">
                                    <?= escapeHTML(date('d/m/Y', strtotime($taux['date']))) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <?= escapeHTML(date('d/m/Y', strtotime($taux['date']))) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">
                            <i class="fas fa-info-circle mb-2" aria-hidden="true"></i>
                            <p class="small">Données disponibles après synchronisation</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center small text-muted">
                    <i class="fas fa-university me-1" aria-hidden="true"></i> Bank Al-Maghrib
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">
            <i class="fas fa-tools me-2" aria-hidden="true"></i>
            <?= escapeHTML(__('home.tools_title')) ?>
        </h2>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow hover-card">
                    <div class="card-body text-center">
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-calculator fa-2x text-white" aria-hidden="true"></i>
                        </div>
                        <h4 class="card-title"><?= escapeHTML(__('menu.calculator')) ?></h4>
                        <p class="card-text">
                            <?= escapeHTML(__('calculator.subtitle')) ?>
                        </p>
                        <a href="calculateur_inflation.php" class="btn btn-success">
                            <?= escapeHTML(__('calculator.calculate')) ?> <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow hover-card">
                    <div class="card-body text-center">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-chart-bar fa-2x text-white" aria-hidden="true"></i>
                        </div>
                        <h4 class="card-title">Inflation Actuelle</h4>
                        <p class="card-text">
                            Consultez l'inflation du mois en cours avec détails par catégorie
                        </p>
                        <a href="inflation_actuelle.php" class="btn btn-primary">
                            Voir les détails <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow hover-card">
                    <div class="card-body text-center">
                        <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-history fa-2x text-white" aria-hidden="true"></i>
                        </div>
                        <h4 class="card-title">Historique Complet</h4>
                        <p class="card-text">
                            Explorez l'évolution de l'inflation depuis <?= escapeHTML(START_YEAR) ?> avec graphiques interactifs
                        </p>
                        <a href="inflation_historique.php" class="btn btn-warning">
                            Voir l'historique <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container row mb-5 mx-auto">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-chart-line me-2" aria-hidden="true"></i>
                    Évolution de l'Inflation - 12 Derniers Mois
                </h4>
            </div>
            <div class="card-body">
                <?php if (!empty($donnees_12mois)): ?>
                    <canvas id="chartEvolution12Mois" height="80"></canvas>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctxEvol = document.getElementById('chartEvolution12Mois').getContext('2d');
                        new Chart(ctxEvol, {
                            type: 'line',
                            data: {
                                labels: [
                                    <?php foreach ($donnees_12mois as $d): ?>
                                        '<?= escapeHTML(getMoisCourt($d["mois"]) . " " . substr($d["annee"], -2)) ?>',
                                    <?php endforeach; ?>
                                ],
                                datasets: [{
                                    label: 'Inflation Annuelle (%)',
                                    data: [
                                        <?php foreach ($donnees_12mois as $d): ?>
                                            <?= escapeHTML($d['inflation_annuelle']) ?>,
                                        <?php endforeach; ?>
                                    ],
                                    borderColor: '#C1272D',
                                    backgroundColor: 'rgba(193, 39, 45, 0.1)',
                                    fill: true,
                                    tension: 0.4,
                                    borderWidth: 3,
                                    pointRadius: 4,
                                    pointHoverRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: true },
                                    title: {
                                        display: true,
                                        text: 'Évolution de l\'Inflation Annuelle (Source: HCP)'
                                    }
                                }
                            }
                        });
                    });
                    </script>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
                        Les données d'évolution seront disponibles après la première synchronisation HCP.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container row mb-5 mx-auto">
    <div class="col-12">
        <h3 class="mb-4">
            <i class="fas fa-chart-pie me-2 text-maroc-rouge" aria-hidden="true"></i>
            Statistiques depuis 2007
        </h3>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-2x text-primary mb-3" aria-hidden="true"></i>
                <h6 class="text-muted mb-2">Inflation Moyenne</h6>
                <h2 class="text-primary mb-0">
                    <?= escapeHTML(number_format($stats_histo['inflation_moyenne'] ?? 0, 2)) ?>%
                </h2>
                <small class="text-muted">
                    <?= escapeHTML($stats_histo['annee_debut'] ?? 2007) ?> - <?= escapeHTML($stats_histo['annee_fin'] ?? date('Y')) ?>
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card border-danger h-100">
            <div class="card-body text-center">
                <i class="fas fa-arrow-up fa-2x text-danger mb-3" aria-hidden="true"></i>
                <h6 class="text-muted mb-2">Inflation Maximum</h6>
                <h2 class="text-danger mb-0">
                    <?= escapeHTML(number_format($stats_histo['inflation_max'] ?? 0, 2)) ?>%
                </h2>
                <small class="text-muted">Record historique</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <i class="fas fa-arrow-down fa-2x text-success mb-3" aria-hidden="true"></i>
                <h6 class="text-muted mb-2">Inflation Minimum</h6>
                <h2 class="text-success mb-0">
                    <?= escapeHTML(number_format($stats_histo['inflation_min'] ?? 0, 2)) ?>%
                </h2>
                <small class="text-muted">Plus bas niveau</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <i class="fas fa-calculator fa-2x text-warning mb-3" aria-hidden="true"></i>
                <h6 class="text-muted mb-2">Inflation Cumulée</h6>
                <h2 class="text-warning mb-0">
                    <?= escapeHTML(number_format($inflation_cumulee, 2)) ?>%
                </h2>
                <small class="text-muted">Depuis <?= escapeHTML($stats_histo['annee_debut'] ?? 2007) ?></small>
            </div>
        </div>
    </div>
    
    <div class="col-12 mt-3">
        <div class="alert alert-light">
            <i class="fas fa-info-circle me-2 text-primary" aria-hidden="true"></i>
            <strong>À savoir :</strong>
            <?= escapeHTML(number_format($inflation_cumulee, 2)) ?>% d'inflation cumulée signifie que
            100 DH de <?= escapeHTML($stats_histo['annee_debut'] ?? 2007) ?> équivalent à
            <?= escapeHTML(number_format(100 * (1 + $inflation_cumulee/100), 2)) ?> DH aujourd'hui.
        </div>
    </div>
</div>

<style>
.hover-card { transition: all 0.3s ease; }
.hover-card:hover { transform: translateY(-10px); }
</style>

<?php
include '../includes/footer.php';
?>
