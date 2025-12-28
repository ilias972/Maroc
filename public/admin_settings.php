<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$page_title = 'Paramètres';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);
$auth->requireAuth();

$message = '';

include '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="h3 mb-0">
        <i class="fas fa-cog text-maroc-rouge me-2"></i>
        Paramètres
    </h1>
</div>

<?= $message ?>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-maroc-rouge text-white">
                <h5 class="mb-0">
                    <i class="fas fa-globe me-2"></i>
                    Configuration Site
                </h5>
            </div>
            <div class="card-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">Nom du site</label>
                        <input type="text" class="form-control" value="<?= SITE_NAME ?>" readonly>
                        <small class="text-muted">Modifiable dans .env</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL du site</label>
                        <input type="url" class="form-control" value="<?= SITE_URL ?>" readonly>
                        <small class="text-muted">Modifiable dans .env</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Année de base IPC</label>
                        <input type="number" class="form-control" value="<?= BASE_YEAR ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Année de début</label>
                        <input type="number" class="form-control" value="<?= START_YEAR ?>" readonly>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-database me-2"></i>
                    Configuration Base de Données
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Hôte</label>
                    <input type="text" class="form-control" value="<?= DB_HOST ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Base de données</label>
                    <input type="text" class="form-control" value="<?= DB_NAME ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Utilisateur</label>
                    <input type="text" class="form-control" value="<?= DB_USER ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Charset</label>
                    <input type="text" class="form-control" value="<?= DB_CHARSET ?>" readonly>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Informations Système
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6>PHP</h6>
                        <p class="mb-0">Version: <?= phpversion() ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6>MySQL</h6>
                        <p class="mb-0">Version: <?= $conn->server_info ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6>Système</h6>
                        <p class="mb-0">Environnement: <?= APP_ENV ?></p>
                        <p class="mb-0">Debug: <?= APP_DEBUG ? 'Activé' : 'Désactivé' ?></p>
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