<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/cache.php';

$page_title = 'Dashboard';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);
$auth->requireAuth();

$cache = new Cache();

// Statistiques de la base de données
$stats_ipc = $conn->query("SELECT COUNT(*) as count FROM ipc_mensuel")->fetch_assoc();
$stats_categories = $conn->query("SELECT COUNT(*) as count FROM inflation_categories")->fetch_assoc();
$stats_villes = $conn->query("SELECT COUNT(DISTINCT ville) as count FROM ipc_villes")->fetch_assoc();
$stats_international = $conn->query("SELECT COUNT(DISTINCT code_pays) as count FROM inflation_internationale")->fetch_assoc();

// Dernière inflation
$derniere_inflation = $conn->query("SELECT * FROM ipc_mensuel ORDER BY annee DESC, mois DESC LIMIT 1")->fetch_assoc();

// Statistiques cache
$cache_stats = $cache->getStats();

// Derniers logs (si table existe)
$derniers_logs = [];
$logs_query = @$conn->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 5");
if ($logs_query) {
    while ($row = $logs_query->fetch_assoc()) {
        $derniers_logs[] = $row;
    }
}

include '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="h3 mb-0">
        <i class="fas fa-tachometer-alt text-maroc-rouge me-2"></i>
        Dashboard
    </h1>
</div>

<!-- Statistiques principales -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Mois de données</p>
                        <h2 class="mb-0"><?= number_format($stats_ipc['count']) ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Catégories</p>
                        <h2 class="mb-0"><?= number_format($stats_categories['count']) ?></h2>
                    </div>
                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                        <i class="fas fa-layer-group fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Villes suivies</p>
                        <h2 class="mb-0"><?= number_format($stats_villes['count']) ?></h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                        <i class="fas fa-map-marker-alt fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Pays comparés</p>
                        <h2 class="mb-0"><?= number_format($stats_international['count']) ?></h2>
                    </div>
                    <div class="bg-info bg-opacity-10 rounded-circle p-3">
                        <i class="fas fa-globe fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inflation actuelle -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-maroc-rouge text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Inflation Actuelle
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <h6 class="text-muted mb-2">
                        <?= getMoisNom($derniere_inflation['mois']) ?> <?= $derniere_inflation['annee'] ?>
                    </h6>
                    <h1 class="display-3 fw-bold text-maroc-rouge mb-3">
                        <?= formatPourcentage($derniere_inflation['inflation_annuelle']) ?>
                    </h1>
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1 text-muted small">Mensuelle</p>
                            <h5 class="text-primary"><?= formatPourcentage($derniere_inflation['inflation_mensuelle']) ?></h5>
                        </div>
                        <div class="col-6">
                            <p class="mb-1 text-muted small">IPC</p>
                            <h5 class="text-success"><?= number_format($derniere_inflation['valeur_ipc'], 2) ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-database me-2"></i>
                    Statistiques Cache
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Fichiers en cache</span>
                        <strong><?= $cache_stats['total_files'] ?></strong>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Taille totale</span>
                        <strong><?= $cache_stats['total_size_human'] ?></strong>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" style="width: 75%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Fichiers expirés</span>
                        <strong><?= $cache_stats['expired_files'] ?></strong>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" style="width: <?= $cache_stats['total_files'] > 0 ? ($cache_stats['expired_files'] / $cache_stats['total_files'] * 100) : 0 ?>%"></div>
                    </div>
                </div>

                <a href="admin_cache.php" class="btn btn-outline-primary btn-sm w-100">
                    <i class="fas fa-arrow-right me-2"></i>
                    Gérer le cache
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2 text-warning"></i>
                    Actions Rapides
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="admin_data.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-file-import me-2"></i>
                            Importer des données
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="admin_cache.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-sync me-2"></i>
                            Vider le cache
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="admin_users.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>
                            Ajouter utilisateur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- Fin admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>