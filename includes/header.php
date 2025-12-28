<?php
// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le titre par défaut si non défini
if (!isset($page_title)) {
    $page_title = 'Accueil';
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/i18n.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    // Configuration SEO par page
    $seo_config = [
        'title' => $page_title . ' - Maroc Inflation',
        'url' => 'http://localhost:8000/' . basename($_SERVER['PHP_SELF'])
    ];

    // Descriptions personnalisées par page
    switch ($page_title) {
        case 'Accueil':
            $seo_config['description'] = 'Suivez l\'inflation au Maroc avec des données officielles du HCP. Calculateur d\'inflation, historique complet depuis 2007, comparaisons internationales.';
            break;
        case 'Calculateur d\'Inflation':
            $seo_config['description'] = 'Calculez l\'évolution du pouvoir d\'achat au Maroc entre deux dates. Outil gratuit basé sur l\'IPC du HCP.';
            break;
        case 'Inflation Actuelle':
            $seo_config['description'] = 'Inflation actuelle au Maroc : taux mensuel et annuel, données par catégorie de produits. Mise à jour mensuelle HCP.';
            break;
        case 'Historique de l\'Inflation':
            $seo_config['description'] = 'Historique complet de l\'inflation au Maroc depuis 2007. Graphiques interactifs et données détaillées du HCP.';
            break;
        case 'Comparaisons Internationales':
            $seo_config['description'] = 'Comparez l\'inflation du Maroc avec la France, l\'Espagne, l\'Algérie et d\'autres pays. Classement et analyse.';
            break;
        case 'Prévisions d\'Inflation':
            $seo_config['description'] = 'Prévisions d\'inflation pour les 6 prochains mois au Maroc. Modèles statistiques basés sur les tendances historiques.';
            break;
        case 'Inflation Régionale':
            $seo_config['description'] = 'Inflation par ville au Maroc : carte interactive avec données de 17 villes et statistiques démographiques.';
            break;
        case 'Exports & Données':
            $seo_config['description'] = 'Téléchargez les exports officiels (historique, régional, comparaisons) et consultez le plan du site.';
            break;
    }

    SEO::generateMetaTags($seo_config);
    ?>

    <title><?= htmlspecialchars($page_title) ?> - Maroc Inflation</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/favicon.png">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger sticky-top shadow">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
                <i class="fas fa-chart-line me-2"></i>
                <?= SITE_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link <?= $page_title === 'Accueil' ? 'active' : '' ?>" href="index.php">
                                <i class="fas fa-home me-1"></i>Accueil
                            </a>
                        </li>

                        <!-- Dropdown Inflation -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="inflationDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-chart-line me-1"></i>Inflation
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item <?= $page_title === 'Inflation Actuelle' ? 'active' : '' ?>" href="inflation_actuelle.php">
                                        <i class="fas fa-calendar-day me-2"></i>Actuelle
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $page_title === 'Historique de l\'Inflation' ? 'active' : '' ?>" href="inflation_historique.php">
                                        <i class="fas fa-history me-2"></i>Historique
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item <?= $page_title === 'Inflation Régionale' ? 'active' : '' ?>" href="inflation_regionale.php">
                                        <i class="fas fa-map-marked-alt me-2"></i>Par Ville
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?= $page_title === 'Calculateur d\'Inflation' ? 'active' : '' ?>" href="calculateur_inflation.php">
                                <i class="fas fa-calculator me-1"></i>Calculateur
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link <?= $page_title === 'Actualités Économiques' ? 'active' : '' ?>" href="actualites.php">
                                <i class="fas fa-newspaper me-1"></i>Actualités
                            </a>
                        </li>

                        <!-- Dropdown Analyses -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="analysesDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-chart-pie me-1"></i>Analyses
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item <?= $page_title === 'Comparaisons Internationales' ? 'active' : '' ?>" href="comparaisons_internationales.php">
                                        <i class="fas fa-globe-europe me-2"></i>Comparaisons Internationales
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= $page_title === 'Prévisions d\'Inflation' ? 'active' : '' ?>" href="previsions.php">
                                        <i class="fas fa-crystal-ball me-2"></i>Prévisions
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item <?= $page_title === 'Graphiques Avancés' ? 'active' : '' ?>" href="graphiques_avances.php">
                                <i class="fas fa-chart-area me-2"></i>Graphiques Avancés
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $page_title === 'Exports & Données' ? 'active' : '' ?>" href="exports.php">
                        <i class="fas fa-file-export me-1"></i>Exports
                    </a>
                </li>
            </ul>

            <!-- Language Switcher -->
            <div class="d-flex ms-3">
                <?php foreach (I18n::getAvailableLangs() as $lang): ?>
                            <a href="<?= I18n::getLangUrl($lang) ?>"
                               class="btn btn-sm <?= I18n::getLang() === $lang ? 'btn-light' : 'btn-outline-light' ?> me-1">
                                <?= strtoupper($lang) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
</div>
</nav>
