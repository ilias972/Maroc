<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

$page_title = 'Exports & Données';

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold">
            <i class="fas fa-file-export text-maroc-rouge me-3"></i>
            Exports & Téléchargements
        </h1>
        <p class="lead text-muted mb-0">
            Téléchargez les jeux de données principaux (historique, régional, comparaisons) ou consultez le plan du site.
        </p>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-database me-2"></i>
                    Jeux de données disponibles
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jeu de données</th>
                                    <th>Formats</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <strong>Historique national</strong>
                                        <br><small class="text-muted">IPC mensuel et inflation nationale</small>
                                    </td>
                                    <td>PDF, Excel</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary me-1" href="export_historique.php?format=pdf">
                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                        </a>
                                        <a class="btn btn-sm btn-outline-success" href="export_historique.php?format=excel">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Inflation par ville</strong>
                                        <br><small class="text-muted">17 villes (démographie, chômage, pauvreté, inflation)</small>
                                    </td>
                                    <td>PDF, Excel</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary me-1" href="export_regional.php?format=pdf">
                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                        </a>
                                        <a class="btn btn-sm btn-outline-success" href="export_regional.php?format=excel">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Comparaisons internationales</strong>
                                        <br><small class="text-muted">Inflation Maroc vs 8 pays suivis</small>
                                    </td>
                                    <td>PDF, Excel</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary me-1" href="export_comparaisons.php?format=pdf">
                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                        </a>
                                        <a class="btn btn-sm btn-outline-success" href="export_comparaisons.php?format=excel">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-sitemap me-2"></i>
                    Plan du site (XML)
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Le plan du site liste toutes les pages publiques et aides les moteurs à découvrir les contenus.
                    </p>
                    <a class="btn btn-secondary w-100 mb-2" href="sitemap.xml.php" target="_blank" rel="noopener">
                        <i class="fas fa-external-link-alt me-2"></i>
                        Ouvrir le sitemap
                    </a>
                    <small class="text-muted d-block">
                        URL: <code><?= SITE_URL ?>/sitemap.xml.php</code>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
