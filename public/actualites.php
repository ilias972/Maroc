<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/i18n.php';

$page_title = __('news.title');

$database = new Database();
$conn = $database->connect();

// Filtres
$source_filter = $_GET['source'] ?? 'all';
$categorie_filter = $_GET['categorie'] ?? 'all';

// Récupérer les actualités
$sql = "SELECT * FROM actualites_economiques WHERE affiche = TRUE";
$params = [];
$types = '';

if ($source_filter !== 'all') {
    $sql .= " AND source = ?";
    $params[] = $source_filter;
    $types .= 's';
}

if ($categorie_filter !== 'all') {
    $sql .= " AND categorie = ?";
    $params[] = $categorie_filter;
    $types .= 's';
}

$sql .= " ORDER BY date_publication DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$actualites = [];
while ($row = $result->fetch_assoc()) {
    $actualites[] = $row;
}

// Récupérer les sources et catégories uniques pour filtres
$sources = $conn->query("SELECT DISTINCT source FROM actualites_economiques WHERE affiche = TRUE ORDER BY source")->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT DISTINCT categorie FROM actualites_economiques WHERE affiche = TRUE ORDER BY categorie")->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <!-- En-tête -->
    <div class="text-center mb-5 fade-in">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-newspaper text-maroc-rouge me-3"></i>
            <?= __('news.title') ?>
        </h1>
        <p class="lead text-muted">
            <?= __('news.subtitle') ?>
        </p>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-4 slide-in-left">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-bold">
                        <i class="fas fa-filter me-2"></i>
                        <?= __('news.source') ?>
                    </label>
                    <select name="source" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $source_filter === 'all' ? 'selected' : '' ?>>Toutes les sources</option>
                        <?php foreach ($sources as $s): ?>
                        <option value="<?= htmlspecialchars($s['source']) ?>" <?= $source_filter === $s['source'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['source']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-bold">
                        <i class="fas fa-tag me-2"></i>
                        <?= __('news.category') ?>
                    </label>
                    <select name="categorie" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $categorie_filter === 'all' ? 'selected' : '' ?>><?= __('news.all_categories') ?></option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= htmlspecialchars($c['categorie']) ?>" <?= $categorie_filter === $c['categorie'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['categorie']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <a href="actualites.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-redo me-2"></i>
                        <?= __('news.reset') ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Nombre de résultats -->
    <div class="mb-4">
        <p class="text-muted">
            <i class="fas fa-info-circle me-2"></i>
            <?= trans('news.results', ['count' => count($actualites)]) ?>
        </p>
    </div>

    <!-- Liste des actualités -->
    <div class="row g-4">
        <?php if (empty($actualites)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h5><?= __('news.no_news') ?></h5>
                    <p class="mb-0">Aucune actualité ne correspond à vos critères de recherche.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($actualites as $actu): ?>
            <div class="col-md-6">
                <div class="card h-100 shadow-sm hover-card fade-in">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge <?= getSourceBadgeClass($actu['source']) ?>">
                            <?= htmlspecialchars($actu['source']) ?>
                        </span>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?= date('d/m/Y', strtotime($actu['date_publication'])) ?>
                        </small>
                    </div>

                    <div class="card-body">
                        <?php if ($actu['categorie']): ?>
                        <span class="badge bg-secondary mb-2">
                            <i class="fas fa-tag me-1"></i>
                            <?= htmlspecialchars($actu['categorie']) ?>
                        </span>
                        <?php endif; ?>

                        <h5 class="card-title">
                            <?= htmlspecialchars($actu['titre']) ?>
                        </h5>

                        <p class="card-text text-muted">
                            <?= htmlspecialchars($actu['description']) ?>
                        </p>
                    </div>

                    <div class="card-footer bg-white border-top">
                        <div class="d-flex gap-2">
                            <?php if ($actu['url_source']): ?>
                            <a href="<?= htmlspecialchars($actu['url_source']) ?>"
                               target="_blank"
                               class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="fas fa-external-link-alt me-2"></i>
                                <?= __('news.view_source') ?>
                            </a>
                            <?php endif; ?>

                            <?php if ($actu['url_rapport']): ?>
                            <a href="<?= htmlspecialchars($actu['url_rapport']) ?>"
                               target="_blank"
                               class="btn btn-sm btn-danger flex-fill">
                                <i class="fas fa-file-pdf me-2"></i>
                                <?= __('news.download_pdf') ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-card {
    transition: all 0.3s ease;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
}
</style>

<?php
function getSourceBadgeClass($source) {
    switch ($source) {
        case 'HCP':
            return 'bg-primary';
        case 'Bank Al-Maghrib':
            return 'bg-success';
        case 'Ministère de l\'Économie et des Finances':
            return 'bg-warning text-dark';
        default:
            return 'bg-info';
    }
}

$conn->close();
include '../includes/footer.php';
?>