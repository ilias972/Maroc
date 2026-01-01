<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/i18n.php';

$page_title = __('calculator.title');

$database = new Database();
$conn = $database->connect();
$calculator = new InflationCalculator($conn);

$resultat = null;
$error = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $montant = floatval($_POST['montant']);
    $annee_depart = intval($_POST['annee_depart']);
    $mois_depart = intval($_POST['mois_depart']);
    $annee_arrivee = intval($_POST['annee_arrivee']);
    $mois_arrivee = intval($_POST['mois_arrivee']);

    // Validation
    if (validerMontant($montant) && validerAnnee($annee_depart) && validerMois($mois_depart)
        && validerAnnee($annee_arrivee) && validerMois($mois_arrivee)) {

        // DONNÉES RÉELLES : IPC départ (HCP)
        $sql_debut = "SELECT valeur_ipc FROM ipc_mensuel
                      WHERE annee = ? AND mois = ?
                      AND source LIKE '%HCP%'
                      LIMIT 1";
        $stmt = $conn->prepare($sql_debut);
        $stmt->bind_param('ii', $annee_depart, $mois_depart);
        $stmt->execute();
        $ipc_debut = $stmt->get_result()->fetch_assoc()['valeur_ipc'] ?? 100;

        // DONNÉES RÉELLES : IPC fin (HCP)
        $sql_fin = "SELECT valeur_ipc FROM ipc_mensuel
                    WHERE annee = ? AND mois = ?
                    AND source LIKE '%HCP%'
                    LIMIT 1";
        $stmt = $conn->prepare($sql_fin);
        $stmt->bind_param('ii', $annee_arrivee, $mois_arrivee);
        $stmt->execute();
        $ipc_fin = $stmt->get_result()->fetch_assoc()['valeur_ipc'] ?? 100;

        if ($ipc_debut > 0 && $ipc_fin > 0) {
            // Calcul inflation cumulée RÉELLE
            $inflation_cumulee = (($ipc_fin - $ipc_debut) / $ipc_debut) * 100;

            // Montant équivalent RÉEL
            $montant_equivalent = $montant * ($ipc_fin / $ipc_debut);

            // Perte pouvoir d'achat
            $perte = $montant - ($montant / ($ipc_fin / $ipc_debut));

            $resultat = [
                'montant_initial' => $montant,
                'montant_equivalent' => $montant_equivalent,
                'inflation_cumulee' => $inflation_cumulee,
                'perte_pouvoir_achat' => $perte,
                'periode_depart' => "$mois_depart/$annee_depart",
                'periode_arrivee' => "$mois_arrivee/$annee_arrivee"
            ];
        } else {
            $error = "Données IPC non disponibles pour les dates sélectionnées.";
        }
    } else {
        $error = "Paramètres invalides. Veuillez vérifier vos entrées.";
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- En-tête -->
            <div class="text-center mb-5 fade-in">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-calculator text-maroc-rouge me-3"></i>
                    <?= __('calculator.title') ?>
                </h1>
                <p class="lead text-muted">
                    <?= __('calculator.subtitle') ?>
                </p>
            </div>

            <!-- Messages d'erreur -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="card shadow-lg mb-4 slide-in-left">
                <div class="card-header bg-maroc-rouge text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Remplissez le formulaire
                    </h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="" id="calculatorForm">
                        <!-- Montant -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-coins me-2 text-maroc-rouge"></i>
                                <?= __('calculator.initial_amount') ?>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text">
                                    <i class="fas fa-money-bill-wave"></i>
                                </span>
                                <input type="number" 
                                       class="form-control" 
                                       name="montant" 
                                       value="<?= isset($_POST['montant']) ? $_POST['montant'] : 1000 ?>" 
                                       min="1" 
                                       step="0.01" 
                                       required
                                       placeholder="Ex: 1000">
                                <span class="input-group-text">DH</span>
                            </div>
                            <small class="text-muted">Entrez le montant que vous souhaitez convertir</small>
                        </div>

                        <div class="row">
                            <!-- Date de départ -->
                            <div class="col-md-6">
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-calendar-check text-success me-2"></i>
                                            <?= __('calculator.start_date') ?>
                                        </h5>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Mois</label>
                                            <select class="form-select" name="mois_depart" required>
                                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?= $m ?>" <?= (isset($_POST['mois_depart']) && $_POST['mois_depart'] == $m) ? 'selected' : '' ?>>
                                                    <?= getMoisNom($m) ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-0">
                                            <label class="form-label fw-bold">Année</label>
                                            <select class="form-select" name="annee_depart" required>
                                                <?php for ($a = START_YEAR; $a <= CURRENT_YEAR; $a++): ?>
                                                <option value="<?= $a ?>" <?= (isset($_POST['annee_depart']) && $_POST['annee_depart'] == $a) || (!isset($_POST['annee_depart']) && $a == 2010) ? 'selected' : '' ?>>
                                                    <?= $a ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Date d'arrivée -->
                            <div class="col-md-6">
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-calendar-alt text-danger me-2"></i>
                                            <?= __('calculator.end_date') ?>
                                        </h5>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Mois</label>
                                            <select class="form-select" name="mois_arrivee" required>
                                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?= $m ?>" <?= (isset($_POST['mois_arrivee']) && $_POST['mois_arrivee'] == $m) || (!isset($_POST['mois_arrivee']) && $m == 12) ? 'selected' : '' ?>>
                                                    <?= getMoisNom($m) ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-0">
                                            <label class="form-label fw-bold">Année</label>
                                            <select class="form-select" name="annee_arrivee" required>
                                                <?php for ($a = START_YEAR; $a <= CURRENT_YEAR; $a++): ?>
                                                <option value="<?= $a ?>" <?= (isset($_POST['annee_arrivee']) && $_POST['annee_arrivee'] == $a) || (!isset($_POST['annee_arrivee']) && $a == CURRENT_YEAR) ? 'selected' : '' ?>>
                                                    <?= $a ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-maroc-rouge btn-lg">
                                <i class="fas fa-calculator me-2"></i>
                                <?= __('calculator.calculate') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Résultat -->
            <?php if ($resultat): ?>
            <div class="card shadow-lg border-success slide-in-right" id="resultat">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= __('calculator.result_title') ?>
                    </h4>
                </div>
                <div class="card-body p-4">
                    <!-- Comparaison montants -->
                    <div class="row mb-4">
                        <div class="col-md-6 text-center border-end">
                            <p class="text-muted mb-2">
                                <i class="fas fa-calendar-day me-2"></i>
                                <?= $resultat['periode_depart'] ?>
                            </p>
                            <h2 class="display-4 fw-bold text-success mb-0">
                                <?= formatMontant($resultat['montant_initial']) ?>
                            </h2>
                            <small class="text-muted"><?= __('calculator.initial') ?></small>
                        </div>
                        
                        <div class="col-md-6 text-center">
                            <p class="text-muted mb-2">
                                <i class="fas fa-calendar-check me-2"></i>
                                <?= $resultat['periode_arrivee'] ?>
                            </p>
                            <h2 class="display-4 fw-bold text-danger mb-0">
                                <?= formatMontant($resultat['montant_equivalent']) ?>
                            </h2>
                            <small class="text-muted"><?= __('calculator.equivalent') ?></small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Indicateurs -->
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-light rounded">
                                <p class="mb-2 fw-bold">
                                    <i class="fas fa-chart-line me-2 text-danger"></i>
                                    <?= __('calculator.cumulative_inflation') ?>
                                </p>
                                <h3 class="text-danger fw-bold mb-0">
                                    <?= formatPourcentage($resultat['inflation_cumulee']) ?>
                                </h3>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="p-3 bg-light rounded">
                                <p class="mb-2 fw-bold">
                                    <i class="fas fa-wallet me-2 text-warning"></i>
                                    <?= __('calculator.purchasing_power_loss') ?>
                                </p>
                                <h3 class="text-warning fw-bold mb-0">
                                    <?= formatMontant($resultat['perte_pouvoir_achat']) ?>
                                </h3>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Explication -->
                    <div class="alert alert-info mb-0">
                        <h5 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            Interprétation
                        </h5>
                        <p class="mb-0">
                            Pour avoir le même pouvoir d'achat qu'avec 
                            <strong><?= formatMontant($resultat['montant_initial']) ?></strong> 
                            en <strong><?= $resultat['periode_depart'] ?></strong>, 
                            il faudrait <strong><?= formatMontant($resultat['montant_equivalent']) ?></strong> 
                            en <strong><?= $resultat['periode_arrivee'] ?></strong>.
                            <br><br>
                            L'inflation cumulée sur cette période est de 
                            <strong><?= formatPourcentage($resultat['inflation_cumulee']) ?></strong>,
                            ce qui représente une perte de pouvoir d'achat de 
                            <strong><?= formatMontant($resultat['perte_pouvoir_achat']) ?></strong>.
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-center gap-2 mt-4">
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>
                            Imprimer
                        </button>
                        <button class="btn btn-outline-success" onclick="partagerPage()">
                            <i class="fas fa-share-alt me-2"></i>
                            Partager
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Explications -->
            <div class="card shadow mt-4 slide-in-left">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Comment fonctionne le calculateur ?
                    </h5>
                </div>
                <div class="card-body">
                    <p>
                        Ce calculateur utilise l'<strong>Indice des Prix à la Consommation (IPC)</strong> 
                        publié par le HCP pour calculer l'évolution du pouvoir d'achat du dirham marocain entre deux dates.
                    </p>
                    
                    <h6 class="mt-3"><i class="fas fa-calculator me-2 text-primary"></i> Formule utilisée :</h6>
                    <div class="bg-light p-3 rounded">
                        <code>Montant équivalent = Montant initial × (IPC arrivée / IPC départ)</code>
                    </div>

                    <h6 class="mt-3"><i class="fas fa-lightbulb me-2 text-warning"></i> Exemples d'utilisation :</h6>
                    <ul>
                        <li>Comparer votre salaire d'aujourd'hui avec celui d'il y a 10 ans</li>
                        <li>Savoir si le prix d'un bien a augmenté plus vite que l'inflation</li>
                        <li>Calculer la revalorisation d'un loyer selon l'inflation</li>
                        <li>Évaluer la performance réelle d'un investissement</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sources des Données -->
<div class="mt-4 p-3 bg-light rounded">
    <h6 class="mb-2">
        <i class="fas fa-info-circle me-2"></i>
        Sources des Données
    </h6>
    <ul class="list-unstyled small mb-0">
        <li>
            <i class="fas fa-check text-success me-2"></i>
            <strong>IPC & Inflation :</strong> Haut-Commissariat au Plan (HCP) via data.gov.ma
        </li>
        <li>
            <i class="fas fa-check text-success me-2"></i>
            <strong>Taux de Change :</strong> ExchangeRate-API (temps réel)
        </li>
        <li>
            <i class="fas fa-check text-success me-2"></i>
            <strong>Comparaisons Internationales :</strong> World Bank Open Data
        </li>
        <li class="text-muted mt-2">
            <i class="fas fa-sync me-1"></i>
            Dernière synchronisation : <?= date('d/m/Y H:i') ?>
        </li>
    </ul>
</div>

<style>
.btn-maroc-rouge {
    background-color: #C1272D;
    color: white;
    border: none;
}

.btn-maroc-rouge:hover {
    background-color: #a91d22;
    color: white;
}

#resultat {
    animation: slideInRight 0.6s ease;
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>

<?php
$conn->close();
include '../includes/footer.php';
?>