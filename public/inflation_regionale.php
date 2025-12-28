<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$page_title = 'Inflation Régionale';

$database = new Database();
$conn = $database->connect();

// Récupérer données régionales
$annee = 2024;
$mois = 12;

$sql = "SELECT
            d.ville, d.region, d.population, d.taux_chomage, d.taux_pauvrete,
            d.latitude, d.longitude, i.inflation_value
        FROM demographie_villes d
        LEFT JOIN ipc_villes i ON d.ville = i.ville AND i.annee = ? AND i.mois = ?
        ORDER BY d.population DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $annee, $mois);
$stmt->execute();
$result = $stmt->get_result();

$villes = [];
while ($row = $result->fetch_assoc()) {
    $villes[] = $row;
}

include '../includes/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="container-fluid py-5">
    <div class="text-center mb-5 fade-in">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-map-marked-alt text-maroc-rouge me-3"></i>
            Inflation par Ville au Maroc
        </h1>
        <p class="lead text-muted">
            Carte interactive des 17 villes avec données démographiques
        </p>
    </div>

    <div class="row">
        <!-- Carte -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow slide-in-left">
                <div class="card-header bg-maroc-rouge text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-map me-2"></i>
                        Carte Interactive du Maroc
                    </h4>
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height: 600px;"></div>
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Cliquez sur un marqueur pour voir les détails
                    </small>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="col-lg-4">
            <div class="card shadow mb-4 fade-in">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Statistiques Nationales
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Calculer statistiques en gérant les valeurs NULL
                    $populations_valides = array_filter(array_column($villes, 'population'), function($v) { return $v !== null; });
                    $total_pop = count($populations_valides) > 0 ? array_sum($populations_valides) : 0;

                    $chomages_valides = array_filter(array_column($villes, 'taux_chomage'), function($v) { return $v !== null; });
                    $moy_chomage = count($chomages_valides) > 0 ? array_sum($chomages_valides) / count($chomages_valides) : 0;

                    $pauvrete_valides = array_filter(array_column($villes, 'taux_pauvrete'), function($v) { return $v !== null; });
                    $moy_pauvrete = count($pauvrete_valides) > 0 ? array_sum($pauvrete_valides) / count($pauvrete_valides) : 0;

                    $inflations_valides = array_filter(array_column($villes, 'inflation_value'), function($v) { return $v !== null; });
                    $moy_inflation = count($inflations_valides) > 0 ? array_sum($inflations_valides) / count($inflations_valides) : 0;
                    ?>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Population Totale</h6>
                        <?php if ($total_pop > 0): ?>
                            <h3 class="text-primary mb-0"><?= number_format($total_pop) ?></h3>
                            <small class="text-muted">habitants (<?= count($populations_valides) ?> villes)</small>
                        <?php else: ?>
                            <h3 class="text-muted mb-0">Non disponible</h3>
                            <small class="text-muted">Aucune donnée démographique</small>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Chômage Moyen</h6>
                        <?php if ($moy_chomage > 0): ?>
                            <h3 class="text-warning mb-0"><?= number_format($moy_chomage, 2) ?>%</h3>
                        <?php else: ?>
                            <h3 class="text-muted mb-0">Non disponible</h3>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Pauvreté Moyenne</h6>
                        <?php if ($moy_pauvrete > 0): ?>
                            <h3 class="text-danger mb-0"><?= number_format($moy_pauvrete, 2) ?>%</h3>
                        <?php else: ?>
                            <h3 class="text-muted mb-0">Non disponible</h3>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div>
                        <h6 class="text-muted mb-1">Inflation Moyenne</h6>
                        <?php if ($moy_inflation > 0): ?>
                            <h3 class="text-success mb-0"><?= number_format($moy_inflation, 2) ?>%</h3>
                            <small class="text-muted">Décembre <?= $annee ?></small>
                        <?php else: ?>
                            <h3 class="text-muted mb-0">Non disponible</h3>
                            <small class="text-muted">Aucune donnée inflation</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Classement -->
            <div class="card shadow slide-in-right">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2"></i>
                        Top 5 - Inflation
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        $villes_avec_inflation = array_filter($villes, function($v) {
                            return $v['inflation_value'] !== null;
                        });
                        usort($villes_avec_inflation, function($a, $b) {
                            return $b['inflation_value'] <=> $a['inflation_value'];
                        });

                        foreach (array_slice($villes_avec_inflation, 0, 5) as $index => $ville):
                        ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                <strong><?= $ville['ville'] ?></strong>
                            </div>
                            <span class="badge bg-danger"><?= formatPourcentage($ville['inflation_value']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau détaillé -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow fade-in">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-table me-2"></i>
                        Données Détaillées par Ville
                    </h4>
                    <div class="btn-group">
                        <a href="export_regional.php?format=pdf" class="btn btn-sm btn-light">
                            <i class="fas fa-file-pdf me-2"></i>
                            PDF
                        </a>
                        <a href="export_regional.php?format=excel" class="btn btn-sm btn-light">
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
                                    <th>Ville</th>
                                    <th>Région</th>
                                    <th class="text-end">Population</th>
                                    <th class="text-end">Chômage</th>
                                    <th class="text-end">Pauvreté</th>
                                    <th class="text-end">Inflation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($villes as $ville): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ville['ville']) ?></strong></td>
                                    <td>
                                        <?php if ($ville['region']): ?>
                                            <?= htmlspecialchars($ville['region']) ?>
                                        <?php else: ?>
                                            <em class="text-muted">Non disponible</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ville['population']): ?>
                                            <?= number_format($ville['population']) ?>
                                        <?php else: ?>
                                            <em class="text-muted">Non disponible</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ville['taux_chomage'] !== null): ?>
                                            <span class="badge bg-warning"><?= number_format($ville['taux_chomage'], 1) ?>%</span>
                                        <?php else: ?>
                                            <em class="text-muted">Non disponible</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ville['taux_pauvrete'] !== null): ?>
                                            <span class="badge bg-danger"><?= number_format($ville['taux_pauvrete'], 1) ?>%</span>
                                        <?php else: ?>
                                            <em class="text-muted">Non disponible</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ville['inflation_value'] !== null): ?>
                                            <span class="badge bg-success"><?= formatPourcentage($ville['inflation_value']) ?></span>
                                        <?php else: ?>
                                            <em class="text-muted">Non disponible</em>
                                        <?php endif; ?>
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
</div>

<!-- Avertissement données manquantes -->
<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Note sur les données :</strong>
            Les données démographiques proviennent de l'API World Cities Database.
            Certaines informations peuvent être indisponibles si l'API ne les fournit pas.
            Pour les données officielles complètes, consultez le
            <a href="https://www.hcp.ma" target="_blank" class="alert-link">
                Recensement HCP 2024
            </a>.
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Données des villes
const villesData = <?= json_encode($villes) ?>;

// Initialiser la carte (centrée sur le Maroc)
const map = L.map('map').setView([31.7917, -7.0926], 6);

// Tuiles OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 18
}).addTo(map);

// Ajouter les marqueurs (seulement pour les villes avec coordonnées)
villesData.forEach(ville => {
    if (!ville.latitude || !ville.longitude) return;

    // Couleur selon inflation (seulement si donnée disponible)
    let color = 'gray'; // Gris par défaut si pas de données
    if (ville.inflation_value !== null && ville.inflation_value !== undefined) {
        if (ville.inflation_value > 1.0) color = 'red';
        else if (ville.inflation_value > 0.7) color = 'orange';
        else color = 'green';
    }

    // Taille du marqueur selon population (si disponible)
    let radius = 8; // Taille par défaut
    if (ville.population && ville.population > 0) {
        radius = Math.max(5, Math.min(20, Math.sqrt(ville.population / 50000)));
    }

    // Créer le marqueur
    const marker = L.circleMarker([ville.latitude, ville.longitude], {
        radius: radius,
        fillColor: color,
        color: '#fff',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.7
    }).addTo(map);

    // Popup avec infos (gérer les valeurs NULL)
    const popupContent = `
        <div style="min-width: 200px;">
            <h5 class="mb-2"><strong>${ville.ville}</strong></h5>
            <p class="mb-1"><small class="text-muted">${ville.region || 'Région non disponible'}</small></p>
            <hr class="my-2">
            <p class="mb-1">👥 Population : <strong>${ville.population ? parseInt(ville.population).toLocaleString() : 'Non disponible'}</strong></p>
            <p class="mb-1">💼 Chômage : <strong>${ville.taux_chomage !== null && ville.taux_chomage !== undefined ? parseFloat(ville.taux_chomage).toFixed(1) + '%' : 'Non disponible'}</strong></p>
            <p class="mb-1">📉 Pauvreté : <strong>${ville.taux_pauvrete !== null && ville.taux_pauvrete !== undefined ? parseFloat(ville.taux_pauvrete).toFixed(1) + '%' : 'Non disponible'}</strong></p>
            <p class="mb-0">📊 Inflation : <strong>${ville.inflation_value !== null && ville.inflation_value !== undefined ? parseFloat(ville.inflation_value).toFixed(1) + '%' : 'Non disponible'}</strong></p>
        </div>
    `;

    marker.bindPopup(popupContent);
});

// Légende
const legend = L.control({position: 'bottomright'});
legend.onAdd = function(map) {
    const div = L.DomUtil.create('div', 'info legend');
    div.innerHTML = `
        <div style="background: white; padding: 10px; border-radius: 5px; box-shadow: 0 0 15px rgba(0,0,0,0.2);">
            <h6><strong>Inflation</strong></h6>
            <div><span style="color: green;">●</span> < 0.7%</div>
            <div><span style="color: orange;">●</span> 0.7% - 1.0%</div>
            <div><span style="color: red;">●</span> > 1.0%</div>
            <hr style="margin: 5px 0;">
            <small>Taille = Population</small>
        </div>
    `;
    return div;
};
legend.addTo(map);
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>
