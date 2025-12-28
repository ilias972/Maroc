<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$page_title = 'Logs Système';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);
$auth->requireAuth();

$message = '';

// Récupérer les logs (si table existe)
$logs = [];
$logs_query = @$conn->query("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 50");
if ($logs_query) {
    while ($row = $logs_query->fetch_assoc()) {
        $logs[] = $row;
    }
}

include '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="h3 mb-0">
        <i class="fas fa-clipboard-list text-maroc-rouge me-2"></i>
        Logs Système
    </h1>
</div>

<?= $message ?>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Logs récents (<?= count($logs) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucun log trouvé. La table admin_logs n'existe pas encore.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Utilisateur</th>
                                    <th>Action</th>
                                    <th>Détails</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($log['username'] ?? 'Système') ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($log['action'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                                    <td><code><?= htmlspecialchars($log['ip_address'] ?? '') ?></code></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div> <!-- Fin admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>