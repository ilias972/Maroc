<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/i18n.php';

$page_title = __('menu.home');

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

// Taux EUR/MAD (dernier disponible)
$taux_eur_data = getDernierTauxChange('EUR', 'VIREMENT');
$taux_eur = $taux_eur_data['cours_mad'] ?? null;
$date_taux = $taux_eur_data['date_taux'] ?? null;
$jours_ecart = $taux_eur_data['jours_ecart'] ?? 999;
$is_recent = $taux_eur_data['is_recent'] ?? false;
$jour_semaine = $taux_eur_data['jour_semaine'] ?? null;

// Catégories d'inflation (dernier mois disponible)
$calculator = new InflationCalculator($conn);
$categories = $calculator->getInflationParCategorie($annee_actuelle, $mois_actuel);

// Statistiques globales
$stats = $calculator->getStatistiques();

$conn->close();

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

// Fonction pour charger l'inflation actuelle
async function loadInflationData() {
    try {
        const response = await fetch('api/get_inflation.php');
        const data = await response.json();

        if (data.success) {
            pageData.inflation = data.inflation;
            pageData.categories = data.categories || [];

            // Mettre à jour l'affichage
            updateInflationDisplay(data);
        }
    } catch (error) {
        console.error('Erreur chargement inflation:', error);
    }
}

// Fonction pour charger les taux de change
async function loadExchangeRates() {
    try {
        // Pour l'instant, utilisons la fonction PHP existante
        // En production, créer une API dédiée pour les taux de change
        const response = await fetch('api/get_exchange_rates.php');
        const data = await response.json();

        if (data.success) {
            pageData.taux_change = data.taux;
            updateExchangeRatesDisplay(data);
        }
    } catch (error) {
        console.error('Erreur chargement taux:', error);
        // Fallback vers les données PHP si l'API n'existe pas encore
        updateExchangeRatesFromPHP();
    }
}

// Fonction pour charger les statistiques
async function loadStats() {
    try {
        const response = await fetch('api/get_stats.php');
        const data = await response.json();

        if (data.success) {
            pageData.stats = data.stats;
            updateStatsDisplay(data);
        }
    } catch (error) {
        console.error('Erreur chargement stats:', error);
    }
}

// Fonction pour mettre à jour l'affichage de l'inflation
function updateInflationDisplay(data) {
    // Mettre à jour la carte principale
    const inflationCard = document.querySelector('.card.bg-white.text-dark h1.display-2');
    if (inflationCard) {
        inflationCard.textContent = data.inflation.annuelle.toFixed(1) + '%';
    }

    // Mettre à jour la date
    const dateElement = document.querySelector('.card.bg-white.text-dark p strong');
    if (dateElement) {
        dateElement.textContent = data.periode.mois_nom + ' ' + data.periode.annee;
    }

    // Mettre à jour les indicateurs clés
    const indicators = document.querySelectorAll('.card .display-5');
    if (indicators.length >= 3) {
        indicators[0].textContent = data.inflation.mensuelle.toFixed(1) + '%'; // Mensuelle
        indicators[1].textContent = (data.inflation.sous_jacente || 0).toFixed(1) + '%'; // Sous-jacente
        indicators[2].textContent = data.inflation.ipc.toFixed(2); // IPC
    }
}

// Fonction pour mettre à jour l'affichage des taux de change
function updateExchangeRatesDisplay(data) {
    if (!data.taux || !data.taux.eur) return;

    const eur = data.taux.eur;

    // Mettre à jour l'alerte principale EUR
    const eurAlert = document.querySelector('.alert.alert-info, .alert.alert-warning');
    if (eurAlert) {
        // Mettre à jour le taux
        const tauxElement = eurAlert.querySelector('strong.fs-4');
        if (tauxElement) {
            tauxElement.textContent = eur.cours_mad.toFixed(4) + ' MAD';
        }

        // Mettre à jour le badge de statut
        const badge = eurAlert.querySelector('.badge');
        if (badge) {
            if (eur.jours_ecart < 1) {
                badge.className = 'badge bg-success fs-6';
                badge.innerHTML = '<i class="fas fa-check-circle me-1"></i>Aujourd\'hui';
            } else if (eur.jours_ecart <= 3) {
                badge.className = 'badge bg-warning fs-6';
                badge.innerHTML = '<i class="fas fa-clock me-1"></i>' + eur.jour_semaine + ' ' + new Date(eur.date_taux).toLocaleDateString('fr-FR');
            } else {
                badge.className = 'badge bg-secondary fs-6';
                badge.innerHTML = '<i class="fas fa-calendar-alt me-1"></i>' + eur.jour_semaine + ' ' + new Date(eur.date_taux).toLocaleDateString('fr-FR');
            }
        }
    }

    // Mettre à jour les autres devises
    if (data.taux.autres) {
        data.taux.autres.forEach((taux, index) => {
            const card = document.querySelectorAll('.col-md-3.mb-3')[index];
            if (card) {
                const valueElement = card.querySelector('.fs-5.fw-bold');
                if (valueElement) {
                    valueElement.textContent = taux.cours_mad.toFixed(4);
                }

                const badge = card.querySelector('.badge');
                if (badge) {
                    if (taux.jours_ecart < 1) {
                        badge.className = 'badge bg-success mt-2';
                        badge.innerHTML = '<i class="fas fa-check"></i> Aujourd\'hui';
                    } else {
                        badge.className = 'badge bg-secondary mt-2';
                        badge.innerHTML = taux.jour_semaine + ' ' + new Date(taux.date_taux).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
                    }
                }
            }
        });
    }
}

// Fallback pour les taux de change depuis PHP
function updateExchangeRatesFromPHP() {
    // Les données PHP sont déjà disponibles dans le HTML
    console.log('Utilisation des données PHP pour les taux de change');
}

// Fonction pour mettre à jour l'affichage des statistiques
function updateStatsDisplay(data) {
    const statCards = document.querySelectorAll('.card.shadow.text-center h2');
    if (statCards.length >= 3 && data.stats && data.stats.periode_complete) {
        statCards[0].textContent = data.stats.periode_complete.moyenne.toFixed(1) + '%';
        statCards[1].textContent = data.stats.periode_complete.maximum.toFixed(1) + '%';
        statCards[2].textContent = data.stats.periode_complete.minimum.toFixed(1) + '%';
    }
}

// Fonction pour mettre à jour le graphique des catégories
function updateCategoriesChart() {
    if (pageData.categories.length === 0) return;

    const ctx = document.getElementById('categoriesChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: pageData.categories.map(c => c.nom || c.categorie.replace('_', ' ')),
            datasets: [{
                label: 'Inflation (%)',
                data: pageData.categories.map(c => parseFloat(c.inflation)),
                backgroundColor: pageData.categories.map(c =>
                    parseFloat(c.inflation) >= 0 ? 'rgba(220, 53, 69, 0.7)' : 'rgba(25, 135, 84, 0.7)'
                ),
                borderColor: pageData.categories.map(c =>
                    parseFloat(c.inflation) >= 0 ? 'rgb(220, 53, 69)' : 'rgb(25, 135, 84)'
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
                    text: 'Inflation par Catégorie - ' + (pageData.inflation ? pageData.inflation.periode.mois_nom + ' ' + pageData.inflation.periode.annee : 'Dernier mois')
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: value => value + '%' }
                }
            }
        }
    });

    // Mettre à jour le tableau des catégories
    updateCategoriesTable();
}

// Fonction pour mettre à jour le tableau des catégories
function updateCategoriesTable() {
    const tbody = document.querySelector('#categoriesChart').closest('.card').querySelector('tbody');
    if (!tbody || pageData.categories.length === 0) return;

    tbody.innerHTML = pageData.categories.map(cat => `
        <tr>
            <td>
                <i class="fas fa-circle me-2" style="color: ${cat.inflation >= 0 ? '#dc3545' : '#198754'}"></i>
                <strong>${cat.nom || cat.categorie.replace('_', ' ')}</strong>
            </td>
            <td class="text-end">
                <span class="badge ${cat.inflation >= 0 ? 'bg-danger' : 'bg-success'}">
                    ${cat.inflation.toFixed(1)}%
                </span>
            </td>
            <td class="text-end text-muted">
                ${cat.ponderation.toFixed(1)}%
            </td>
            <td class="text-center">
                ${cat.inflation > 0 ? '<i class="fas fa-arrow-up text-danger"></i>' :
                  cat.inflation < 0 ? '<i class="fas fa-arrow-down text-success"></i>' :
                  '<i class="fas fa-minus text-muted"></i>'}
            </td>
        </tr>
    `).join('');
}

// Charger toutes les données au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadInflationData();
    loadExchangeRates();
    loadStats();

    // Mettre à jour les graphiques après un court délai
    setTimeout(() => {
        updateCategoriesChart();
    }, 1000);
});
</script>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0 slide-in-left">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-chart-line me-3"></i>
                    <?= __('home.hero_title') ?>
                </h1>
                <p class="lead mb-4">
                    <?= trans('home.hero_subtitle', ['year' => START_YEAR]) ?>
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="calculateur_inflation.php" class="btn btn-light btn-lg">
                        <i class="fas fa-calculator me-2"></i>
                        <?= __('menu.calculator') ?>
                    </a>
                    <a href="inflation_historique.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-history me-2"></i>
                        <?= __('menu.history') ?>
                    </a>
                </div>
            </div>
            <div class="col-lg-4 slide-in-right">
                <div class="card bg-white text-dark shadow-lg">
                    <div class="card-body text-center p-4">
                        <h6 class="text-muted mb-2"><?= __('home.current_inflation') ?></h6>
                        <h1 class="display-2 fw-bold text-maroc-rouge mb-2">
                            <?= formatPourcentage($inflation_actuelle['inflation_annuelle']) ?>
                        </h1>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?= getMoisNom($inflation_actuelle['mois']) ?> <?= $inflation_actuelle['annee'] ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Indicateurs Clés -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <!-- Inflation Mensuelle -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-calendar-day fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title"><?= __('home.monthly_inflation') ?></h5>
                        <h2 class="display-5 fw-bold text-primary mb-2">
                            <?= formatPourcentage($inflation_actuelle['inflation_mensuelle']) ?>
                        </h2>
                        <p class="text-muted">Variation sur le dernier mois</p>
                    </div>
                </div>
            </div>

            <!-- Inflation Sous-jacente -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-filter fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title"><?= __('home.core_inflation') ?></h5>
                        <h2 class="display-5 fw-bold text-warning mb-2">
                            <?= formatPourcentage($inflation_actuelle['inflation_sous_jacente'] ?? 0) ?>
                        </h2>
                        <p class="text-muted">Hors prix volatils et tarifs publics</p>
                    </div>
                </div>
            </div>

            <!-- IPC Base -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm hover-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-balance-scale fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title"><?= __('home.ipc_index') ?></h5>
                        <h2 class="display-5 fw-bold text-success mb-2">
                            <?= number_format($inflation_actuelle['valeur_ipc'], 2) ?>
                        </h2>
                        <p class="text-muted">Base <?= BASE_YEAR ?> = 100</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Taux de change - 4 devises principales -->
<div class="row mb-5">
    <div class="col-12">
        <h3 class="mb-4">
            <i class="fas fa-exchange-alt me-2 text-maroc-rouge"></i>
            Taux de Change Officiels
        </h3>
        <p class="text-muted mb-4">
            Source : Bank Al-Maghrib |
            Mise à jour : <?= date('d/m/Y') ?>
            <?php
            $dayOfWeek = date('N');
            if ($dayOfWeek >= 6) {
                echo '<span class="badge bg-warning">Marché fermé (week-end)</span>';
            }
            ?>
        </p>
    </div>

    <?php
    // Récupérer les 4 taux principaux
    $devises_principales = ['EUR', 'USD', 'GBP', 'CHF'];
    $taux_devises = [];

    $database = new Database();
    $conn = $database->connect();

    foreach ($devises_principales as $devise) {
        $devise_var = $devise;
        $sql = "SELECT cours_mad, date_taux FROM taux_change
                WHERE devise = ? AND type_taux = 'VIREMENT'
                ORDER BY date_taux DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $devise_var);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $jours_ecart = (strtotime(date('Y-m-d')) - strtotime($data['date_taux'])) / 86400;

            $taux_devises[$devise] = [
                'cours' => $data['cours_mad'],
                'date' => $data['date_taux'],
                'jours_ecart' => $jours_ecart
            ];
        } else {
            // Si pas de données, laisser vide avec message
            $taux_devises[$devise] = null;
        }
    }

    $conn->close();

    // Infos devises
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
        <div class="card h-100 border-<?= $info['couleur'] ?>">
            <div class="card-body text-center">
                <i class="fas <?= $info['icone'] ?> fa-3x text-<?= $info['couleur'] ?> mb-3"></i>

                <h6 class="text-muted mb-2"><?= $info['nom'] ?> (<?= $devise ?>)</h6>

                <?php if ($taux): ?>
                    <h3 class="text-<?= $info['couleur'] ?> mb-2">
                        <?= number_format($taux['cours'], 4) ?>
                    </h3>
                    <small class="text-muted">MAD</small>

                    <div class="mt-3">
                        <?php if ($taux['jours_ecart'] < 1): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> Aujourd'hui
                            </span>
                        <?php elseif ($taux['jours_ecart'] <= 3): ?>
                            <span class="badge bg-warning">
                                <?= date('d/m/Y', strtotime($taux['date'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary">
                                <?= date('d/m/Y', strtotime($taux['date'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted">
                        <i class="fas fa-info-circle mb-2"></i>
                        <p class="small">Données disponibles après synchronisation</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center small text-muted">
                <i class="fas fa-university me-1"></i>
                Bank Al-Maghrib
            </div>
        </div>
    </div>

    <?php endforeach; ?>
</div>

<!-- Outils Principaux -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">
            <i class="fas fa-tools me-2"></i>
            <?= __('home.tools_title') ?>
        </h2>

        <div class="row g-4">
            <!-- Calculateur -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow hover-card">
                    <div class="card-body text-center">
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-calculator fa-2x text-white"></i>
                        </div>
                        <h4 class="card-title"><?= __('menu.calculator') ?></h4>
                        <p class="card-text">
                            <?= __('calculator.subtitle') ?>
                        </p>
                        <a href="calculateur_inflation.php" class="btn btn-success">
                            <?= __('calculator.calculate') ?> <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Inflation Actuelle -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow hover-card">
                    <div class="card-body text-center">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-chart-bar fa-2x text-white"></i>
                        </div>
                        <h4 class="card-title">Inflation Actuelle</h4>
                        <p class="card-text">
                            Consultez l'inflation du mois en cours avec détails par catégorie
                        </p>
                        <a href="inflation_actuelle.php" class="btn btn-primary">
                            Voir les détails <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Historique -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow hover-card">
                    <div class="card-body text-center">
                        <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-history fa-2x text-white"></i>
                        </div>
                        <h4 class="card-title">Historique Complet</h4>
                        <p class="card-text">
                            Explorez l'évolution de l'inflation depuis <?= START_YEAR ?> avec graphiques interactifs
                        </p>
                        <a href="inflation_historique.php" class="btn btn-warning">
                            Voir l'historique <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Graphique Évolution Inflation 12 derniers mois -->
<div class="row mb-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Évolution de l'Inflation - 12 Derniers Mois
                </h4>
            </div>
            <div class="card-body">
                <?php
                // Récupérer les 12 derniers mois d'inflation
                $database = new Database();
                $conn = $database->connect();

                $sql_12mois = "SELECT annee, mois, inflation_annuelle
                               FROM ipc_mensuel
                               ORDER BY annee DESC, mois DESC
                               LIMIT 12";

                $result_12mois = $conn->query($sql_12mois);
                $donnees_12mois = [];
                while ($row = $result_12mois->fetch_assoc()) {
                    $donnees_12mois[] = $row;
                }
                $donnees_12mois = array_reverse($donnees_12mois); // Chronologique

                $conn->close();
                ?>

                <?php if (!empty($donnees_12mois)): ?>
                    <canvas id="chartEvolution12Mois" height="80"></canvas>

                    <script>
                    const ctxEvol = document.getElementById('chartEvolution12Mois').getContext('2d');
                    new Chart(ctxEvol, {
                        type: 'line',
                        data: {
                            labels: [
                                <?php foreach ($donnees_12mois as $d): ?>
                                    '<?= getMoisCourt($d["mois"]) ?> <?= substr($d["annee"], -2) ?>',
                                <?php endforeach; ?>
                            ],
                            datasets: [{
                                label: 'Inflation Annuelle (%)',
                                data: [
                                    <?php foreach ($donnees_12mois as $d): ?>
                                        <?= $d['inflation_annuelle'] ?>,
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
                    </script>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Les données d'évolution seront disponibles après la première synchronisation HCP.
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted small">
                <i class="fas fa-info-circle me-1"></i>
                Source : HCP - Derniers 12 mois disponibles
            </div>
        </div>
    </div>
</div>

<!-- Statistiques Historiques -->
<div class="row mb-5">
    <div class="col-12">
        <h3 class="mb-4">
            <i class="fas fa-chart-pie me-2 text-maroc-rouge"></i>
            Statistiques depuis 2007
        </h3>
    </div>

    <?php
    // Calculer statistiques historiques RÉELLES
    $database = new Database();
    $conn = $database->connect();

    $sql_stats = "SELECT
        MIN(annee) as annee_debut,
        MAX(annee) as annee_fin,
        COUNT(*) as nb_mois,
        AVG(inflation_annuelle) as inflation_moyenne,
        MAX(inflation_annuelle) as inflation_max,
        MIN(inflation_annuelle) as inflation_min,
        MIN(valeur_ipc) as ipc_min,
        MAX(valeur_ipc) as ipc_max
      FROM ipc_mensuel";

    $stats_histo = $conn->query($sql_stats)->fetch_assoc();

    // Inflation cumulée
    $sql_cumul = "SELECT
        (SELECT valeur_ipc FROM ipc_mensuel ORDER BY annee ASC, mois ASC LIMIT 1) as ipc_debut,
        (SELECT valeur_ipc FROM ipc_mensuel ORDER BY annee DESC, mois DESC LIMIT 1) as ipc_fin";
    $cumul = $conn->query($sql_cumul)->fetch_assoc();

    $inflation_cumulee = 0;
    if ($cumul['ipc_debut'] && $cumul['ipc_fin']) {
        $inflation_cumulee = (($cumul['ipc_fin'] - $cumul['ipc_debut']) / $cumul['ipc_debut']) * 100;
    }

    $conn->close();
    ?>

    <!-- Carte 1 : Inflation Moyenne -->
    <div class="col-md-3 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-2x text-primary mb-3"></i>
                <h6 class="text-muted mb-2">Inflation Moyenne</h6>
                <h2 class="text-primary mb-0">
                    <?= number_format($stats_histo['inflation_moyenne'] ?? 0, 2) ?>%
                </h2>
                <small class="text-muted">
                    <?= $stats_histo['annee_debut'] ?? 2007 ?> - <?= $stats_histo['annee_fin'] ?? date('Y') ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Carte 2 : Inflation Maximum -->
    <div class="col-md-3 mb-3">
        <div class="card border-danger h-100">
            <div class="card-body text-center">
                <i class="fas fa-arrow-up fa-2x text-danger mb-3"></i>
                <h6 class="text-muted mb-2">Inflation Maximum</h6>
                <h2 class="text-danger mb-0">
                    <?= number_format($stats_histo['inflation_max'] ?? 0, 2) ?>%
                </h2>
                <small class="text-muted">
                    Record historique
                </small>
            </div>
        </div>
    </div>

    <!-- Carte 3 : Inflation Minimum -->
    <div class="col-md-3 mb-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <i class="fas fa-arrow-down fa-2x text-success mb-3"></i>
                <h6 class="text-muted mb-2">Inflation Minimum</h6>
                <h2 class="text-success mb-0">
                    <?= number_format($stats_histo['inflation_min'] ?? 0, 2) ?>%
                </h2>
                <small class="text-muted">
                    Plus bas niveau
                </small>
            </div>
        </div>
    </div>

    <!-- Carte 4 : Inflation Cumulée -->
    <div class="col-md-3 mb-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <i class="fas fa-calculator fa-2x text-warning mb-3"></i>
                <h6 class="text-muted mb-2">Inflation Cumulée</h6>
                <h2 class="text-warning mb-0">
                    <?= number_format($inflation_cumulee, 2) ?>%
                </h2>
                <small class="text-muted">
                    Depuis 2007
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-5">
    <div class="col-12">
        <div class="alert alert-light">
            <i class="fas fa-info-circle me-2 text-primary"></i>
            <strong>À savoir :</strong>
            <?= number_format($inflation_cumulee, 2) ?>% d'inflation cumulée signifie que
            100 DH de <?= $stats_histo['annee_debut'] ?> équivalent à
            <?= number_format(100 * (1 + $inflation_cumulee/100), 2) ?> DH aujourd'hui.
        </div>
    </div>
</div>

<!-- FAQ / À propos -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="text-center mb-5">
                    <i class="fas fa-question-circle me-2"></i>
                    Questions Fréquentes
                </h2>

                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Qu'est-ce que l'inflation ?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                L'inflation mesure la hausse générale et durable des prix des biens et services dans une économie.
                                Au Maroc, elle est calculée par le HCP via l'Indice des Prix à la Consommation (IPC), qui suit
                                l'évolution des prix d'un panier représentatif de <?= count($categories) ?> catégories de produits.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Comment est calculé l'IPC au Maroc ?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                L'IPC est calculé sur la base d'un panier de 546 articles représentatifs de la consommation
                                des ménages marocains. Chaque catégorie a une pondération qui reflète son importance dans
                                le budget des ménages. La base de référence actuelle est <?= BASE_YEAR ?> = 100.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                D'où proviennent les données ?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Toutes les données présentées sur ce site proviennent du
                                <strong>Haut-Commissariat au Plan (HCP)</strong>, l'organisme officiel marocain chargé
                                de la production des statistiques nationales. Les données sont publiées mensuellement.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Comment utiliser le calculateur d'inflation ?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Le calculateur vous permet de comparer le pouvoir d'achat d'une somme en dirhams entre deux dates.
                                Par exemple, vous pouvez savoir combien il faudrait aujourd'hui pour avoir le même pouvoir d'achat
                                qu'avec 1000 DH en 2010. C'est utile pour indexer un salaire, un loyer ou évaluer un investissement.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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


<style>
.hover-card {
    transition: all 0.3s ease;
}

.hover-card:hover {
    transform: translateY(-10px);
}
</style>

<?php
include '../includes/footer.php';
?>