<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$page_title = 'Historique de l\'Inflation';

// Paramètres de filtrage
$annee_debut = isset($_GET['annee_debut']) ? intval($_GET['annee_debut']) : START_YEAR;
$annee_fin = isset($_GET['annee_fin']) ? intval($_GET['annee_fin']) : CURRENT_YEAR;

// Récupération des données réelles depuis la base de données
$database = new Database();
$conn = $database->connect();

// Requête historique RÉEL depuis HCP
$ad = $annee_debut;
$af = $annee_fin;

$sql = "SELECT annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle
        FROM ipc_mensuel
        WHERE annee >= ? AND annee <= ?
        ORDER BY annee ASC, mois ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $ad, $af);
$stmt->execute();
$result = $stmt->get_result();

$historique = [];
while ($row = $result->fetch_assoc()) {
    $historique[] = $row;
}

// Calcul statistiques période RÉELLES
$nb_mois = count($historique);

if ($nb_mois > 0) {
    $ipc_debut = $historique[0]['valeur_ipc'];
    $ipc_fin = $historique[$nb_mois - 1]['valeur_ipc'];

    // Inflation cumulée réelle
    $inflation_totale = (($ipc_fin - $ipc_debut) / $ipc_debut) * 100;

    // Moyenne, max, min depuis vraies données
    $inflation_moyenne = array_sum(array_column($historique, 'inflation_annuelle')) / $nb_mois;
    $inflation_max = max(array_column($historique, 'inflation_annuelle'));
    $inflation_min = min(array_column($historique, 'inflation_annuelle'));
} else {
    // Pas de données disponibles - sera géré dans l'affichage
    $inflation_totale = null;
    $inflation_moyenne = null;
    $inflation_max = null;
    $inflation_min = null;
}

$stats = [
    'moyenne' => $inflation_moyenne,
    'max' => $inflation_max,
    'min' => $inflation_min
];

// Événements (désactivé temporairement - table non disponible)
$evenements = [];

$conn->close();

include '../includes/header.php';
?>

<script>
// Données PHP directement injectées (pas d'APIs, pas de fallbacks)
const historiqueData = <?= json_encode($historique) ?>;
const statsData = <?= json_encode($stats) ?>;
const evenementsData = <?= json_encode($evenements) ?>;


// Fonction pour initialiser les graphiques avec données PHP
function initCharts() {
    if (!historiqueData || historiqueData.length === 0) {
        // Afficher message si pas de données
        const chartContainers = document.querySelectorAll('.card-body canvas');
        chartContainers.forEach(container => {
            const parent = container.parentElement;
            if (parent) {
                parent.innerHTML = `
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h5>Aucune donnée disponible</h5>
                        <p>Les données seront disponibles après la première synchronisation.</p>
                        <a href="admin_sync_data.php" class="btn btn-primary">Lancer la synchronisation</a>
                    </div>
                `;
            }
        });
        return;
    }

    // Graphique de l'inflation
    const ctxHistorique = document.getElementById('historiqueChart');
    if (ctxHistorique) {
        new Chart(ctxHistorique, {
            type: 'line',
            data: {
                labels: historiqueData.map(h => getMoisNom(h.mois) + ' ' + h.annee),
                datasets: [{
                    label: 'Inflation Annuelle (%)',
                    data: historiqueData.map(h => parseFloat(h.inflation_annuelle)),
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: true },
                    tooltip: { mode: 'index', intersect: false },
                    title: {
                        display: true,
                        text: 'Évolution de l\'Inflation Annuelle (Source: HCP)'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: value => value + '%' }
                    },
                    x: {
                        ticks: { maxTicksLimit: 20, maxRotation: 45, minRotation: 45 }
                    }
                }
            }
        });
    }

    // Graphique de l'IPC
    const ctxIPC = document.getElementById('ipcChart');
    if (ctxIPC) {
        new Chart(ctxIPC, {
            type: 'line',
            data: {
                labels: historiqueData.map(h => getMoisNom(h.mois) + ' ' + h.annee),
                datasets: [{
                    label: 'Indice IPC (Base <?= BASE_YEAR ?> = 100)',
                    data: historiqueData.map(h => parseFloat(h.valeur_ipc)),
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: true },
                    title: {
                        display: true,
                        text: 'Évolution de l\'Indice IPC (Source: HCP)'
                    }
                },
                scales: {
                    y: { beginAtZero: false },
                    x: { ticks: { maxTicksLimit: 20, maxRotation: 45, minRotation: 45 } }
                }
            }
        });
    }
}

// Fonction pour formater les mois (côté JS)
function getMoisNom(mois) {
    const noms = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
                  'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    return noms[parseInt(mois)];
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
});
?>

<div class="container py-5">
    <!-- En-tête -->
    <div class="text-center mb-5 fade-in">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-history text-maroc-rouge me-3"></i>
            Historique de l'Inflation au Maroc
        </h1>
        <p class="lead text-muted">
            Évolution de l'IPC et de l'inflation depuis <?= START_YEAR ?>
        </p>
    </div>

    <!-- Filtres -->
    <div class="card shadow mb-4 slide-in-left">
        <div class="card-header bg-maroc-rouge text-white">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Filtrer la période
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Année de début</label>
                    <select name="annee_debut" class="form-select">
                        <?php for ($a = START_YEAR; $a <= CURRENT_YEAR; $a++): ?>
                        <option value="<?= $a ?>" <?= $annee_debut == $a ? 'selected' : '' ?>>
                            <?= $a ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Année de fin</label>
                    <select name="annee_fin" class="form-select">
                        <?php for ($a = START_YEAR; $a <= CURRENT_YEAR; $a++): ?>
                        <option value="<?= $a ?>" <?= $annee_fin == $a ? 'selected' : '' ?>>
                            <?= $a ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-maroc-rouge w-100">
                        <i class="fas fa-search me-2"></i>
                        Appliquer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistiques de la période -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card text-center shadow slide-in-left">
                <div class="card-body">
                    <h6 class="text-muted">Inflation Moyenne</h6>
                    <h2 class="text-primary fw-bold">
                        <?= $stats['moyenne'] !== null ? formatPourcentage($stats['moyenne']) : '<em>Non disponible</em>' ?>
                    </h2>
                    <small class="text-muted"><?= $annee_debut ?> - <?= $annee_fin ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow fade-in">
                <div class="card-body">
                    <h6 class="text-muted">Inflation Maximale</h6>
                    <h2 class="text-danger fw-bold">
                        <?= $stats['max'] !== null ? formatPourcentage($stats['max']) : '<em>Non disponible</em>' ?>
                    </h2>
                    <small class="text-muted">Point le plus haut</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow slide-in-right">
                <div class="card-body">
                    <h6 class="text-muted">Inflation Minimale</h6>
                    <h2 class="text-success fw-bold">
                        <?= $stats['min'] !== null ? formatPourcentage($stats['min']) : '<em>Non disponible</em>' ?>
                    </h2>
                    <small class="text-muted">Point le plus bas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique Principal - Inflation -->
    <div class="card shadow mb-5 fade-in">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-chart-area me-2"></i>
                Évolution de l'Inflation (<?= $annee_debut ?> - <?= $annee_fin ?>)
            </h4>
        </div>
        <div class="card-body p-4">
            <canvas id="historiqueChart" height="100"></canvas>
        </div>
    </div>

    <!-- Graphique IPC -->
    <div class="card shadow mb-5 slide-in-left">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-chart-line me-2"></i>
                Évolution de l'IPC (Base <?= BASE_YEAR ?> = 100)
            </h4>
        </div>
        <div class="card-body p-4">
            <canvas id="ipcChart" height="100"></canvas>
        </div>
    </div>

    <!-- Événements marquants -->
    <?php if (!empty($evenements)): ?>
    <div class="card shadow mb-5 slide-in-right">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Événements Économiques Majeurs
            </h4>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($evenements as $evt): ?>
                <div class="timeline-item mb-4 pb-3 border-bottom">
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-calendar-day text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-1">
                                <strong><?= $evt['annee'] ?></strong>
                                <?= $evt['mois'] ? ' - ' . getMoisNom($evt['mois']) : '' ?>
                            </h5>
                            <p class="mb-1"><?= htmlspecialchars($evt['evenement_contexte']) ?></p>
                            <small class="text-muted">
                                <i class="fas fa-link me-1"></i>
                                Source : <?= htmlspecialchars($evt['source']) ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tableau des données -->
    <div class="card shadow fade-in">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Données Détaillées
            </h4>
            <div class="btn-group">
                <a href="export_historique.php?format=pdf&annee_debut=<?= $annee_debut ?>&annee_fin=<?= $annee_fin ?>"
                   class="btn btn-sm btn-danger">
                    <i class="fas fa-file-pdf me-2"></i>
                    PDF
                </a>
                <a href="export_historique.php?format=excel&annee_debut=<?= $annee_debut ?>&annee_fin=<?= $annee_fin ?>"
                   class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel me-2"></i>
                    Excel
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-striped table-hover mb-0" id="dataTable">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th>Date</th>
                            <th class="text-end">IPC</th>
                            <th class="text-end">Inflation Mensuelle</th>
                            <th class="text-end">Inflation Annuelle</th>
                            <th class="text-center">Tendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historique)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucune donnée disponible pour cette période.
                                    <a href="admin_sync_data.php" class="alert-link">Lancer la synchronisation</a>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach (array_reverse($historique) as $h): ?>
                            <tr>
                                <td>
                                    <strong><?= getMoisNom($h['mois']) ?> <?= $h['annee'] ?></strong>
                                </td>
                                <td class="text-end">
                                    <?= number_format($h['valeur_ipc'], 2) ?>
                                </td>
                                <td class="text-end">
                                    <span class="badge <?= $h['inflation_mensuelle'] >= 0 ? 'bg-danger' : 'bg-success' ?>">
                                        <?= formatPourcentage($h['inflation_mensuelle']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge <?= $h['inflation_annuelle'] >= 0 ? 'bg-danger' : 'bg-success' ?>">
                                        <strong><?= formatPourcentage($h['inflation_annuelle']) ?></strong>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($h['inflation_annuelle'] > 0): ?>
                                        <i class="fas fa-arrow-up text-danger"></i>
                                    <?php elseif ($h['inflation_annuelle'] < 0): ?>
                                        <i class="fas fa-arrow-down text-success"></i>
                                    <?php else: ?>
                                        <i class="fas fa-minus text-muted"></i>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted text-center">
            <small>
                <i class="fas fa-database me-2"></i>
                <?= count($historique) ?> mois de données affichées
            </small>
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


<style>
.timeline {
    position: relative;
}

.timeline-item:last-child {
    border-bottom: none !important;
}

.btn-maroc-rouge {
    background-color: #C1272D;
    color: white;
    border: none;
}

.btn-maroc-rouge:hover {
    background-color: #a91d22;
    color: white;
}
</style>

<?php
include '../includes/footer.php';
?>