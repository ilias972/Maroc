<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$page_title = 'Gestion Utilisateurs';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);
$auth->requireAuth();

$message = '';
$current_user = $auth->getCurrentUser();

// Traitement des actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_user':
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            if (!empty($username) && !empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sss', $username, $email, $password_hash);

                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">✅ Utilisateur créé avec succès</div>';
                } else {
                    $message = '<div class="alert alert-danger">❌ Erreur : ' . $conn->error . '</div>';
                }
            }
            break;

        case 'toggle_active':
            $user_id = intval($_POST['user_id']);
            $sql = "UPDATE admin_users SET is_active = NOT is_active WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $message = '<div class="alert alert-success">✅ Statut modifié</div>';
            break;
    }
}

// Récupérer tous les utilisateurs
$users_query = $conn->query("SELECT * FROM admin_users ORDER BY created_at DESC");
$users = [];
while ($row = $users_query->fetch_assoc()) {
    $users[] = $row;
}

include '../includes/admin_header.php';
?>

<div class="admin-header">
    <h1 class="h3 mb-0">
        <i class="fas fa-users text-maroc-rouge me-2"></i>
        Gestion des Utilisateurs
    </h1>
</div>

<?= $message ?>

<div class="row g-4 mb-4">
    <!-- Formulaire ajout utilisateur -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-maroc-rouge text-white">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Nouvel Utilisateur
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" name="password" required>
                        <small class="text-muted">Min. 8 caractères, 1 majuscule, 1 chiffre</small>
                    </div>

                    <button type="submit" name="action" value="add_user" class="btn btn-maroc-rouge w-100">
                        <i class="fas fa-plus me-2"></i>
                        Créer l'utilisateur
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste des utilisateurs -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Utilisateurs (<?= count($users) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Créé le</th>
                                <th>Dernière connexion</th>
                                <th>Statut</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-user-circle me-2 text-primary"></i>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                    <?php if ($user['id'] === $current_user['id']): ?>
                                        <span class="badge bg-success ms-2">Vous</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais' ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($user['id'] !== $current_user['id']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="action" value="toggle_active"
                                                    class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </form>
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

</div> <!-- Fin admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $conn->close(); ?>