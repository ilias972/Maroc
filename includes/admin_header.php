<?php
// Vérifier l'authentification
if (!isset($auth) || !$auth->isAuthenticated()) {
    header('Location: admin_login.php');
    exit;
}

$current_user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Admin Maroc Inflation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .admin-sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }

        .admin-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .admin-nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }

        .admin-nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: #C1272D;
        }

        .admin-nav-link.active {
            background: rgba(193, 39, 45, 0.2);
            color: white;
            border-left-color: #C1272D;
        }

        .admin-nav-link i {
            width: 25px;
        }

        .admin-header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            .admin-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="admin-sidebar">
    <div class="p-4 border-bottom border-secondary">
        <h4 class="mb-0">
            <i class="fas fa-shield-alt me-2"></i>
            Admin Panel
        </h4>
        <small class="text-white-50">Maroc Inflation</small>
    </div>

    <nav class="py-3">
        <a href="admin_dashboard.php" class="admin-nav-link <?= $page_title === 'Dashboard' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>

        <a href="admin_cache.php" class="admin-nav-link <?= $page_title === 'Administration Cache' ? 'active' : '' ?>">
            <i class="fas fa-database"></i>
            Cache
        </a>

        <a href="admin_data.php" class="admin-nav-link <?= $page_title === 'Gestion Données' ? 'active' : '' ?>">
            <i class="fas fa-file-import"></i>
            Données
        </a>

        <a href="admin_users.php" class="admin-nav-link <?= $page_title === 'Gestion Utilisateurs' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            Utilisateurs
        </a>

        <a href="admin_logs.php" class="admin-nav-link <?= $page_title === 'Logs Système' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-list"></i>
            Logs
        </a>

        <a href="admin_settings.php" class="admin-nav-link <?= $page_title === 'Paramètres' ? 'active' : '' ?>">
            <i class="fas fa-cog"></i>
            Paramètres
        </a>

        <a href="admin_actualites.php" class="admin-nav-link <?= $page_title === 'Gestion des Actualités' ? 'active' : '' ?>">
            <i class="fas fa-newspaper"></i>
            Actualités
        </a>

        <hr class="border-secondary my-3">

        <a href="index.php" class="admin-nav-link" target="_blank">
            <i class="fas fa-external-link-alt"></i>
            Voir le site
        </a>

        <a href="secure-logout-xyz2024.php" class="admin-nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i>
            Déconnexion
        </a>
    </nav>

    <div class="position-absolute bottom-0 w-100 p-3 border-top border-secondary">
        <div class="d-flex align-items-center">
            <div class="bg-maroc-rouge rounded-circle d-flex align-items-center justify-content-center me-2"
                 style="width: 40px; height: 40px;">
                <i class="fas fa-user text-white"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold small"><?= htmlspecialchars($current_user['username']) ?></div>
                <div class="text-white-50 small">Administrateur</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="admin-content">