<?php
// PROTECTION : Vérifier l'authentification
session_start();

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/cache.php';
require_once '../includes/auth.php';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);
$auth->requireAuth(); // Redirection automatique si non connecté

$current_user = $auth->getCurrentUser();

$page_title = 'Administration Cache';

$cache = new Cache();
$message = '';

// Actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'clear':
            $cache->clear();
            $message = '<div class="alert alert-success">✅ Cache vidé avec succès</div>';
            break;
        case 'cleanup':
            $cleaned = $cache->cleanup();
            $message = "<div class='alert alert-info'>🧹 $cleaned fichiers expirés nettoyés</div>";
            break;
    }
}

$stats = $cache->getStats();

include '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="h3 mb-0">
        <i class="fas fa-database text-maroc-rouge me-3"></i>
        Administration du Cache
    </h1>
</div>

    <?= $message ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <i class="fas fa-file fa-3x text-primary mb-3"></i>
                    <h3 class="fw-bold"><?= $stats['total_files'] ?></h3>
                    <p class="text-muted mb-0">Fichiers en cache</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <i class="fas fa-hdd fa-3x text-success mb-3"></i>
                    <h3 class="fw-bold"><?= $stats['total_size_human'] ?></h3>
                    <p class="text-muted mb-0">Taille totale</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                    <h3 class="fw-bold"><?= $stats['expired_files'] ?></h3>
                    <p class="text-muted mb-0">Fichiers expirés</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow text-center">
                <div class="card-body">
                    <i class="fas fa-folder fa-3x text-info mb-3"></i>
                    <h3 class="fw-bold">Cache</h3>
                    <p class="text-muted mb-0">Dossier actif</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card shadow mb-4">
        <div class="card-header bg-maroc-rouge text-white">
            <h4 class="mb-0">
                <i class="fas fa-tools me-2"></i>
                Actions
            </h4>
        </div>
        <div class="card-body">
            <form method="POST" class="d-flex gap-3">
                <button type="submit" name="action" value="cleanup" class="btn btn-warning">
                    <i class="fas fa-broom me-2"></i>
                    Nettoyer les fichiers expirés
                </button>

                <button type="submit" name="action" value="clear" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir vider tout le cache ?')">
                    <i class="fas fa-trash me-2"></i>
                    Vider tout le cache
                </button>
            </form>
        </div>
    </div>

    <!-- Informations -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Informations
            </h4>
        </div>
        <div class="card-body">
            <p><strong>Dossier cache :</strong> <code><?= $stats['cache_dir'] ?></code></p>
            <p><strong>Durée de vie par défaut :</strong> 1 jour (86400 secondes)</p>
            <p class="mb-0"><strong>Type de cache :</strong> Fichiers JSON</p>

            <hr>

            <h5>Données mises en cache :</h5>
            <ul>
                <li>Inflation actuelle (1 jour)</li>
                <li>Historique complet (1 jour)</li>
                <li>Inflation par catégorie (1 jour)</li>
                <li>Statistiques (1 jour)</li>
            </ul>

            <div class="alert alert-info mb-0">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Conseil :</strong> Le cache se régénère automatiquement après expiration. Videz-le manuellement uniquement après une mise à jour des données.
            </div>
        </div>
    </div>
</div> <!-- Fin admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>