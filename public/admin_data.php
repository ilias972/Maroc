<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/cache.php';

$page_title = 'Gestion Données';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);
$auth->requireAuth();

$cache = new Cache();
$message = '';

// Traitement des actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'clear_cache':
            $cache->clear();
            $message = '<div class="alert alert-success">✅ Cache vidé avec succès</div>';
            break;

        case 'recalculate_stats':
            // Recalculer les statistiques
            $message = '<div class="alert alert-success">✅ Statistiques recalculées</div>';
            break;
    }
}

// Statistiques
$total_ipc = $conn->query("SELECT COUNT(*) as count FROM ipc_mensuel")->fetch_assoc()['count'];
$total_categories = $conn->query("SELECT COUNT(*) as count FROM inflation_categories")->fetch_assoc()['count'];
$total_villes = $conn->query("SELECT COUNT(*) as count FROM ipc_villes")->fetch_assoc()['count'];
$total_previsions = $conn->query("SELECT COUNT(*) as count FROM previsions_inflation")->fetch_assoc()['count'];

include '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="h3 mb-0">
        <i class="fas fa-file-import text-maroc-rouge me-2"></i>
        Gestion des Données
    </h1>
</div>

<?= $message ?>

<!-- Statistiques des données -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                <h3 class="fw-bold"><?= number_format($total_ipc) ?></h3>
                <p class="text-muted mb-0">Mois IPC</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-layer-group fa-3x text-success mb-3"></i>
                <h3 class="fw-bold"><?= number_format($total_categories) ?></h3>
                <p class="text-muted mb-0">Catégories</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-map-marker-alt fa-3x text-warning mb-3"></i>
                <h3 class="fw-bold"><?= number_format($total_villes) ?></h3>
                <p class="text-muted mb-0">Données villes</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-crystal-ball fa-3x text-info mb-3"></i>
                <h3 class="fw-bold"><?= number_format($total_previsions) ?></h3>
                <p class="text-muted mb-0">Prévisions</p>
            </div>
        </div>
    </div>
</div>

<!-- Import manuel -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-maroc-rouge text-white">
                <h5 class="mb-0">
                    <i class="fas fa-file-csv me-2"></i>
                    Importer CSV
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Fichier CSV</label>
                        <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                        <small class="text-muted">Format : Annee,Mois,IPC,Inflation_Mensuelle,Inflation_Annuelle</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Délimiteur</label>
                        <select class="form-select" name="delimiter">
                            <option value=",">Virgule (,)</option>
                            <option value=";">Point-virgule (;)</option>
                        </select>
                    </div>

                    <button type="submit" name="action" value="import_csv" class="btn btn-maroc-rouge">
                        <i class="fas fa-upload me-2"></i>
                        Importer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-sync me-2"></i>
                    Scripts d'import
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Exécuter les scripts d'import depuis le terminal</p>

                <div class="mb-3">
                    <strong>Import CSV standard :</strong>
                    <pre class="bg-light p-2 rounded mt-2"><code>php data/import_hcp_api.php --source=csv --file=data.csv</code></pre>
                </div>

                <div class="mb-3">
                    <strong>Import depuis API :</strong>
                    <pre class="bg-light p-2 rounded mt-2"><code>php data/import_hcp_api.php --source=api</code></pre>
                </div>

                <div>
                    <strong>Calculer prévisions :</strong>
                    <pre class="bg-light p-2 rounded mt-2"><code>php data/calculate_previsions.php</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export de données -->
<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-download me-2"></i>
                    Export de Données
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="../api/get_ipc.php?annee_debut=2007&annee_fin=2025"
                           class="btn btn-outline-success w-100" download>
                            <i class="fas fa-file-export me-2"></i>
                            IPC Complet (JSON)
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="../api/get_comparaisons.php"
                           class="btn btn-outline-success w-100" download>
                            <i class="fas fa-globe me-2"></i>
                            Comparaisons (JSON)
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="../api/get_previsions.php"
                           class="btn btn-outline-success w-100" download>
                            <i class="fas fa-crystal-ball me-2"></i>
                            Prévisions (JSON)
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="../api/get_regional.php"
                           class="btn btn-outline-success w-100" download>
                            <i class="fas fa-map me-2"></i>
                            Données régionales (JSON)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions de maintenance -->
<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-tools me-2"></i>
                    Maintenance
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <button type="submit" name="action" value="clear_cache"
                                class="btn btn-outline-warning w-100">
                            <i class="fas fa-trash me-2"></i>
                            Vider le cache
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="action" value="recalculate_stats"
                                class="btn btn-outline-info w-100">
                            <i class="fas fa-calculator me-2"></i>
                            Recalculer statistiques
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-danger w-100"
                                onclick="return confirm('Attention : Cette action supprimera toutes les prévisions. Continuer ?')">
                            <i class="fas fa-sync me-2"></i>
                            Régénérer prévisions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</div> <!-- Fin admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>