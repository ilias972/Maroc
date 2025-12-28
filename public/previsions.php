<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$page_title = 'Prévisions d\'Inflation';

$database = new Database();
$conn = $database->connect();

// Historique (12 derniers mois)
$sql_hist = "SELECT annee, mois, inflation_annuelle
             FROM ipc_mensuel
             ORDER BY annee DESC, mois DESC
             LIMIT 12";
$result_hist = $conn->query($sql_hist);
$historique = [];
while ($row = $result_hist->fetch_assoc()) {
    $historique[] = $row;
}
$historique = array_reverse($historique);

// Prévisions
$sql_prev = "SELECT * FROM previsions_inflation ORDER BY annee, mois";
$result_prev = $conn->query($sql_prev);
$previsions = [];
while ($row = $result_prev->fetch_assoc()) {
    $previsions[] = $row;
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5 fade-in">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-crystal-ball text-maroc-rouge me-3"></i>
            Prévisions d'Inflation
        </h1>
        <p class="lead text-muted">
            Projection de l'inflation pour les 6 prochains mois
        </p>
        <div class="alert alert-warning d-inline-block">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Ces prévisions sont basées sur des modèles statistiques simples et ne garantissent pas les valeurs réelles
        </div>
    </div>

    <!-- Graphique avec zone de prévision -->
    <div class="card shadow mb-5 slide-in-left">
        <div class="card-header bg-maroc-rouge text-white">
            <h4 class="mb-0">
                <i class="fas fa-chart-area me-2"></i>
                Évolution et Prévisions
            </h4>
        </div>
        <div class="card-body p-4">
            <canvas id="previsionsChart" height="100"></canvas>
        </div>
    </div>

    <!-- Tableau des prévisions -->
    <div class="card shadow fade-in">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Prévisions Détaillées
            </h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Période</th>
                            <th class="text-end">Prévision</th>
                            <th class="text-end">Intervalle Min</th>
                            <th class="text-end">Intervalle Max</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previsions as $prev): ?>
                        <tr>
                            <td><strong><?= getMoisNom($prev['mois']) ?> <?= $prev['annee'] ?></strong></td>
                            <td class="text-end">
                                <span class="badge bg-primary"><?= formatPourcentage($prev['inflation_prevue']) ?></span>
                            </td>
                            <td class="text-end text-success"><?= formatPourcentage($prev['inflation_min']) ?></td>
                            <td class="text-end text-danger"><?= formatPourcentage($prev['inflation_max']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const historique = <?= json_encode($historique) ?>;
const previsions = <?= json_encode($previsions) ?>;

// Combiner historique et prévisions
const labels = [
    ...historique.map(h => getMoisNom(h.mois) + ' ' + h.annee),
    ...previsions.map(p => getMoisNom(p.mois) + ' ' + p.annee)
];

const data_hist = historique.map(h => parseFloat(h.inflation_annuelle));
const data_prev = new Array(historique.length).fill(null).concat(
    previsions.map(p => parseFloat(p.inflation_prevue))
);
const data_min = new Array(historique.length).fill(null).concat(
    previsions.map(p => parseFloat(p.inflation_min))
);
const data_max = new Array(historique.length).fill(null).concat(
    previsions.map(p => parseFloat(p.inflation_max))
);

const ctx = document.getElementById('previsionsChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Historique',
                data: data_hist,
                borderColor: 'rgb(193, 39, 45)',
                backgroundColor: 'rgba(193, 39, 45, 0.1)',
                borderWidth: 3,
                tension: 0.4
            },
            {
                label: 'Prévision',
                data: data_prev,
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.4
            },
            {
                label: 'Intervalle confiance',
                data: data_max,
                borderColor: 'rgba(255, 193, 7, 0.3)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                fill: '+1',
                borderWidth: 1,
                pointRadius: 0
            },
            {
                data: data_min,
                borderColor: 'rgba(255, 193, 7, 0.3)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                fill: false,
                borderWidth: 1,
                pointRadius: 0
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                labels: {
                    filter: item => item.text !== undefined
                }
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

function getMoisNom(mois) {
    const noms = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin',
                  'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    return noms[parseInt(mois)];
}
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>