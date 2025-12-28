<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$page_title = 'Administration - Connexion';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);

// Si déjà connecté, rediriger
if ($auth->isAuthenticated()) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
$step = isset($_SESSION['pending_2fa_user_id']) ? 2 : 1;
$code_2fa_dev = ''; // Pour affichage en dev

// Étape 1 : Login classique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $result = $auth->login($username, $password);

        if ($result['success'] && isset($result['require_2fa'])) {
            $step = 2;
            $code_2fa_dev = $result['code_2fa']; // DEV UNIQUEMENT
        } elseif (!$result['success']) {
            $error = $result['error'];
        }
    }
}

// Étape 2 : Vérification 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_2fa') {
    $code = trim($_POST['code'] ?? '');

    if (empty($code)) {
        $error = 'Veuillez entrer le code';
    } else {
        $result = $auth->complete2FALogin($code);

        if ($result['success']) {
            header('Location: admin_dashboard.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            max-width: 450px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(135deg, #C1272D 0%, #8B1A1F 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .code-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 0.5rem;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg login-card mx-auto">
                <div class="login-header text-center py-4">
                    <i class="fas fa-shield-halved fa-3x mb-3"></i>
                    <h2 class="mb-0">Administration Sécurisée</h2>
                    <p class="mb-0">Maroc Inflation</p>
                </div>

                <div class="card-body p-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($step === 1): ?>
                        <!-- ÉTAPE 1 : Login -->
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="login">

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-user me-2"></i>
                                    Nom d'utilisateur
                                </label>
                                <input type="text"
                                       name="username"
                                       class="form-control form-control-lg"
                                       placeholder="admin"
                                       required
                                       autofocus>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-key me-2"></i>
                                    Mot de passe
                                </label>
                                <input type="password"
                                       name="password"
                                       class="form-control form-control-lg"
                                       placeholder="••••••••"
                                       required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-lg text-white" style="background: #C1272D;">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Se connecter
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- ÉTAPE 2 : Vérification 2FA -->
                        <div class="text-center mb-4">
                            <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                            <h4>Authentification à 2 Facteurs</h4>
                            <p class="text-muted">Entrez le code de vérification</p>
                        </div>

                        <?php if ($code_2fa_dev): ?>
                            <div class="alert alert-warning">
                                <strong>MODE DÉVELOPPEMENT :</strong><br>
                                Votre code 2FA : <strong class="fs-3"><?= $code_2fa_dev ?></strong>
                                <br><small>(En production, ce code serait envoyé par email)</small>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="verify_2fa">

                            <div class="mb-4">
                                <label class="form-label fw-bold text-center d-block">Code (6 chiffres)</label>
                                <input type="text"
                                       name="code"
                                       class="form-control form-control-lg code-input"
                                       placeholder="000000"
                                       maxlength="6"
                                       pattern="[0-9]{6}"
                                       required
                                       autofocus>
                                <small class="text-muted">Le code expire dans 10 minutes</small>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-lg btn-primary">
                                    <i class="fas fa-check me-2"></i>
                                    Vérifier
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <div class="text-center">
                            <a href="secure-access-xyz2024.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour à la connexion
                            </a>
                        </div>
                    <?php endif; ?>

                    <hr class="my-4">

                    <div class="text-center">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-home me-2"></i>
                            Retour au site
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>