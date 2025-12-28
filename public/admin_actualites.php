<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);

$auth->requireAuth();

$page_title = 'Gestion des Actualités';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $titre = $_POST['titre'];
                $description = $_POST['description'];
                $source = $_POST['source'];
                $categorie = $_POST['categorie'];
                $date_publication = $_POST['date_publication'];
                $url_source = $_POST['url_source'];
                $url_rapport = $_POST['url_rapport'];
                $affiche = isset($_POST['affiche']) ? 1 : 0;

                $sql = "INSERT INTO actualites_economiques (titre, description, source, categorie, date_publication, url_source, url_rapport, affiche)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssssssi',
                    $titre,
                    $description,
                    $source,
                    $categorie,
                    $date_publication,
                    $url_source,
                    $url_rapport,
                    $affiche
                );
                $stmt->execute();
                header('Location: admin_actualites.php?success=added');
                exit;

            case 'edit':
                $titre = $_POST['titre'];
                $description = $_POST['description'];
                $source = $_POST['source'];
                $categorie = $_POST['categorie'];
                $date_publication = $_POST['date_publication'];
                $url_source = $_POST['url_source'];
                $url_rapport = $_POST['url_rapport'];
                $affiche = isset($_POST['affiche']) ? 1 : 0;
                $id = $_POST['id'];

                $sql = "UPDATE actualites_economiques SET
                        titre = ?, description = ?, source = ?, categorie = ?,
                        date_publication = ?, url_source = ?, url_rapport = ?, affiche = ?
                        WHERE id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssssssii',
                    $titre,
                    $description,
                    $source,
                    $categorie,
                    $date_publication,
                    $url_source,
                    $url_rapport,
                    $affiche,
                    $id
                );
                $stmt->execute();
                header('Location: admin_actualites.php?success=updated');
                exit;

            case 'delete':
                $id = $_POST['id'];
                $sql = "DELETE FROM actualites_economiques WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $id);
                $stmt->execute();
                header('Location: admin_actualites.php?success=deleted');
                exit;
        }
    }
}

// Récupération des données pour l'édition
$edit_item = null;
if ($action === 'edit' && $id) {
    $edit_id = intval($id);
    $sql = "SELECT * FROM actualites_economiques WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_item = $stmt->get_result()->fetch_assoc();
}

// Liste des actualités
$sql = "SELECT * FROM actualites_economiques ORDER BY date_publication DESC";
$result = $conn->query($sql);
$actualites = $result->fetch_all(MYSQLI_ASSOC);

include '../includes/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-newspaper me-2"></i>
                    Gestion des Actualités
                </h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>
                    Ajouter une actualité
                </button>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php
                    switch ($_GET['success']) {
                        case 'added': echo 'Actualité ajoutée avec succès'; break;
                        case 'updated': echo 'Actualité modifiée avec succès'; break;
                        case 'deleted': echo 'Actualité supprimée avec succès'; break;
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Titre</th>
                                    <th>Source</th>
                                    <th>Catégorie</th>
                                    <th>Date</th>
                                    <th>Visible</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actualites as $actu): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars(substr($actu['titre'], 0, 50)) ?>...</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= htmlspecialchars($actu['source']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($actu['categorie']) ?></span>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($actu['date_publication'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($actu['affiche']): ?>
                                            <span class="badge bg-success">Oui</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?action=edit&id=<?= $actu['id'] ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger" onclick="deleteItem(<?= $actu['id'] ?>, '<?= htmlspecialchars(addslashes($actu['titre'])) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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

<!-- Modal Ajout -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une actualité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Titre *</label>
                                <input type="text" name="titre" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Source *</label>
                                <select name="source" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <option value="HCP">HCP</option>
                                    <option value="Bank Al-Maghrib">Bank Al-Maghrib</option>
                                    <option value="Ministère de l'Économie et des Finances">Ministère</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Catégorie</label>
                                <select name="categorie" class="form-select">
                                    <option value="">Choisir...</option>
                                    <option value="Inflation">Inflation</option>
                                    <option value="Politique Monétaire">Politique Monétaire</option>
                                    <option value="Conjoncture">Conjoncture</option>
                                    <option value="Budget">Budget</option>
                                    <option value="Indicateurs">Indicateurs</option>
                                    <option value="Emploi">Emploi</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date de publication *</label>
                                <input type="date" name="date_publication" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">URL Source</label>
                                <input type="url" name="url_source" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">URL Rapport PDF</label>
                                <input type="url" name="url_rapport" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="affiche" class="form-check-input" id="afficheAdd" checked>
                            <label class="form-check-label" for="afficheAdd">
                                Afficher publiquement
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition -->
<?php if ($edit_item): ?>
<div class="modal fade show" id="editModal" tabindex="-1" style="display: block;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier l'actualité</h5>
                <a href="admin_actualites.php" class="btn-close"></a>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Titre *</label>
                                <input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($edit_item['titre']) ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Source *</label>
                                <select name="source" class="form-select" required>
                                    <option value="HCP" <?= $edit_item['source'] === 'HCP' ? 'selected' : '' ?>>HCP</option>
                                    <option value="Bank Al-Maghrib" <?= $edit_item['source'] === 'Bank Al-Maghrib' ? 'selected' : '' ?>>Bank Al-Maghrib</option>
                                    <option value="Ministère de l'Économie et des Finances" <?= $edit_item['source'] === "Ministère de l'Économie et des Finances" ? 'selected' : '' ?>>Ministère</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_item['description']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Catégorie</label>
                                <select name="categorie" class="form-select">
                                    <option value="">Choisir...</option>
                                    <option value="Inflation" <?= $edit_item['categorie'] === 'Inflation' ? 'selected' : '' ?>>Inflation</option>
                                    <option value="Politique Monétaire" <?= $edit_item['categorie'] === 'Politique Monétaire' ? 'selected' : '' ?>>Politique Monétaire</option>
                                    <option value="Conjoncture" <?= $edit_item['categorie'] === 'Conjoncture' ? 'selected' : '' ?>>Conjoncture</option>
                                    <option value="Budget" <?= $edit_item['categorie'] === 'Budget' ? 'selected' : '' ?>>Budget</option>
                                    <option value="Indicateurs" <?= $edit_item['categorie'] === 'Indicateurs' ? 'selected' : '' ?>>Indicateurs</option>
                                    <option value="Emploi" <?= $edit_item['categorie'] === 'Emploi' ? 'selected' : '' ?>>Emploi</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date de publication *</label>
                                <input type="date" name="date_publication" class="form-control" value="<?= $edit_item['date_publication'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">URL Source</label>
                                <input type="url" name="url_source" class="form-control" value="<?= htmlspecialchars($edit_item['url_source']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">URL Rapport PDF</label>
                                <input type="url" name="url_rapport" class="form-control" value="<?= htmlspecialchars($edit_item['url_rapport']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="affiche" class="form-check-input" id="afficheEdit" <?= $edit_item['affiche'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="afficheEdit">
                                Afficher publiquement
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="admin_actualites.php" class="btn btn-secondary">Annuler</a>
                    <button type="submit" class="btn btn-primary">Modifier</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function deleteItem(id, title) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette actualité ?\n\n"' + title + '"')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
$conn->close();
include '../includes/admin_footer.php';
?>