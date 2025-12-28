<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

echo "=== TEST DES FONCTIONS DE TAUX DE CHANGE ===\n\n";

$database = new Database();
$conn = $database->connect();

// Test fonction getDernierTauxChange
echo "1. Test getDernierTauxChange('EUR'):\n";
$taux_eur = getDernierTauxChange('EUR', 'VIREMENT');
if ($taux_eur) {
    echo "   ✅ EUR trouvé: " . number_format($taux_eur['cours_mad'], 4) . " MAD\n";
    echo "   📅 Date: " . $taux_eur['date_taux'] . " (" . $taux_eur['jour_semaine'] . ")\n";
    echo "   ⏰ Écart: " . $taux_eur['jours_ecart'] . " jours\n";
    echo "   📊 Récent: " . ($taux_eur['is_recent'] ? 'Oui' : 'Non') . "\n";
} else {
    echo "   ❌ Aucun taux EUR trouvé\n";
}

echo "\n2. Test afficherTauxChange():\n";
echo "   " . afficherTauxChange($taux_eur, 'EUR') . "\n";

echo "\n3. Test autres devises:\n";
$devises = ['USD', 'GBP', 'CHF'];
foreach ($devises as $devise) {
    $taux = getDernierTauxChange($devise, 'VIREMENT');
    if ($taux) {
        echo "   ✅ $devise: " . number_format($taux['cours_mad'], 4) . " MAD\n";
    } else {
        echo "   ❌ $devise: Non trouvé\n";
    }
}

echo "\n4. Test devise inexistante:\n";
$taux_test = getDernierTauxChange('JPY', 'VIREMENT');
if (!$taux_test) {
    echo "   ✅ JPY correctement non trouvé\n";
} else {
    echo "   ❌ JPY trouvé (inattendu)\n";
}

$conn->close();
echo "\n✅ Tests terminés !\n";
?>