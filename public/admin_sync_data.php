<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: secure-access-xyz2024.php');
    exit;
}

$page_title = 'Synchronisation Données';
$active_page = 'sync';

$syncResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_source'])) {
    $source = $_POST['sync_source'];
    $output = [];
    $return = 0;

    switch ($source) {
        case 'bam':
            exec('php ' . __DIR__ . '/../data/import_bank_al_maghrib.php 2>&1', $output, $return);
            break;
        case 'hcp':
            exec('php ' . __DIR__ . '/../data/import_hcp_ckan.php 2>&1', $output, $return);
            break;
        case 'worldbank':
            exec('php ' . __DIR__ . '/../data/import_world_bank.php 2>&1', $output, $return);
            break;
        case 'all':
            exec('php ' . __DIR__ . '/../data/cron_daily_sync.php 2>&1', $output, $return);
            break;
    }

    $syncResult = [
        'source' => $source,
        'success' => ($return === 0),
        'output' => implode("\n", $output)
    ];
}

require_once '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <h1 class="h2 mb-4">
        <i class="fas fa-sync-alt me-2"></i>
        Synchronisation des Données
    </h1>

    <?php if ($syncResult): ?>
    <div class="alert alert-<?= $syncResult['success'] ? 'success' : 'danger' ?> alert-dismissible">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <h5><?= $syncResult['success'] ? '✅ Réussie' : '❌ Erreur' ?></h5>
        <pre class="mb-0 small" style="max-height:300px;overflow-y:auto"><?= htmlspecialchars($syncResult['output']) ?></pre>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Bank Al-Maghrib -->
        <div class="col-lg-3 mb-4">
            <div class="card h-100 border-maroc-rouge">
                <div class="card-header bg-maroc-rouge text-white">
                    <h5 class="mb-0"><i class="fas fa-university me-2"></i>Bank Al-Maghrib</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Taux de change quotidiens</p>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-check text-success me-2"></i>Cours BBE</li>
                        <li><i class="fas fa-check text-success me-2"></i>Virements</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <form method="POST">
                        <input type="hidden" name="sync_source" value="bam">
                        <button type="submit" class="btn btn-maroc-rouge w-100">
                            <i class="fas fa-sync me-2"></i>Synchroniser
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- HCP -->
        <div class="col-lg-3 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>HCP</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">IPC mensuel</p>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-check text-success me-2"></i>Inflation</li>
                        <li><i class="fas fa-check text-success me-2"></i>Base 2017</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <form method="POST">
                        <input type="hidden" name="sync_source" value="hcp">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sync me-2"></i>Synchroniser
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- World Bank -->
        <div class="col-lg-3 mb-4">
            <div class="card h-100 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-globe me-2"></i>World Bank</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Comparaisons</p>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-check text-success me-2"></i>8 pays</li>
                        <li><i class="fas fa-check text-success me-2"></i>2020-2024</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <form method="POST">
                        <input type="hidden" name="sync_source" value="worldbank">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-sync me-2"></i>Synchroniser
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tout -->
        <div class="col-lg-3 mb-4">
            <div class="card h-100 border-dark">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-sync-alt me-2"></i>Complet</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Synchronisation complète</p>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-arrow-right text-primary me-2"></i>Bank Al-Maghrib</li>
                        <li><i class="fas fa-arrow-right text-primary me-2"></i>HCP</li>
                        <li><i class="fas fa-arrow-right text-primary me-2"></i>World Bank</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <form method="POST" onsubmit="return confirm('Synchroniser toutes les sources ? Cela peut prendre plusieurs minutes.')">
                        <input type="hidden" name="sync_source" value="all">
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="fas fa-bolt me-2"></i>Tout Synchroniser
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- État des données -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-database me-2"></i>
                        État des Données
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>Dernière MAJ</th>
                                    <th>Nb. Enregistrements</th>
                                    <th>État</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $database = new Database();
                                $conn = $database->connect();

                                // Bank Al-Maghrib
                                $result = $conn->query("SELECT COUNT(*) as total, MAX(created_at) as last_update FROM taux_change");
                                $bam = $result->fetch_assoc();
                                ?>
                                <tr>
                                    <td><i class="fas fa-university text-maroc-rouge me-2"></i>Bank Al-Maghrib</td>
                                    <td><?= $bam['last_update'] ?? 'Jamais' ?></td>
                                    <td><?= $bam['total'] ?></td>
                                    <td><?= $bam['total'] > 0 ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-warning">Vide</span>' ?></td>
                                </tr>

                                <?php
                                // HCP
                                $result = $conn->query("SELECT COUNT(*) as total, MAX(created_at) as last_update FROM ipc_mensuel WHERE source LIKE '%HCP%'");
                                $hcp = $result->fetch_assoc();
                                ?>
                                <tr>
                                    <td><i class="fas fa-chart-line text-primary me-2"></i>HCP (IPC)</td>
                                    <td><?= $hcp['last_update'] ?? 'Jamais' ?></td>
                                    <td><?= $hcp['total'] ?></td>
                                    <td><?= $hcp['total'] > 0 ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-warning">Vide</span>' ?></td>
                                </tr>

                                <?php
                                // World Bank
                                $result = $conn->query("SELECT COUNT(*) as total, MAX(created_at) as last_update FROM inflation_internationale WHERE source = 'World Bank API'");
                                $wb = $result->fetch_assoc();
                                ?>
                                <tr>
                                    <td><i class="fas fa-globe text-success me-2"></i>World Bank</td>
                                    <td><?= $wb['last_update'] ?? 'Jamais' ?></td>
                                    <td><?= $wb['total'] ?></td>
                                    <td><?= $wb['total'] > 0 ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-warning">Vide</span>' ?></td>
                                </tr>

                                <?php $conn->close(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>