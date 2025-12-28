<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

echo "=== TEST DES FONCTIONS ===\n\n";

$database = new Database();
$conn = $database->connect();
$calculator = new InflationCalculator($conn);

// Test 1: Inflation actuelle
echo "1. Test inflation actuelle :\n";
$inflation = $calculator->getInflationActuelle();
echo "   Dernier mois : " . getMoisNom($inflation['mois']) . " {$inflation['annee']}\n";
echo "   Inflation annuelle : " . formatPourcentage($inflation['inflation_annuelle']) . "\n\n";

// Test 2: Calculateur
echo "2. Test calculateur (1000 DH de 2010 à 2025) :\n";
$resultat = $calculator->calculerPouvoirAchat(1000, 2010, 1, 2025, 12);
if (!isset($resultat['error'])) {
    echo "   Montant initial : " . formatMontant($resultat['montant_initial']) . "\n";
    echo "   Équivalent : " . formatMontant($resultat['montant_equivalent']) . "\n";
    echo "   Inflation cumulée : " . formatPourcentage($resultat['inflation_cumulee']) . "\n\n";
} else {
    echo "   Erreur : {$resultat['error']}\n\n";
}

// Test 3: Historique
echo "3. Test historique (2020-2022) :\n";
$historique = $calculator->getHistorique(2020, 2022);
echo "   Nombre de mois : " . count($historique) . "\n";
echo "   Premier mois : " . getMoisNom($historique[0]['mois']) . " {$historique[0]['annee']}\n";
echo "   Dernier mois : " . getMoisNom($historique[count($historique)-1]['mois']) . " {$historique[count($historique)-1]['annee']}\n\n";

// Test 4: Catégories
echo "4. Test catégories (12/2025) :\n";
$categories = $calculator->getInflationParCategorie(2025, 12);
echo "   Nombre de catégories : " . count($categories) . "\n";
if (!empty($categories)) {
    echo "   Première catégorie : " . getCategorieName($categories[0]['categorie']) .
         " (" . formatPourcentage($categories[0]['inflation_value']) . ")\n\n";
}

// Test 5: Statistiques
echo "5. Test statistiques (2007-2025) :\n";
$stats = $calculator->getStatistiques(2007, 2025);
echo "   Inflation moyenne : " . formatPourcentage($stats['moyenne']) . "\n";
echo "   Inflation max : " . formatPourcentage($stats['max']) . "\n";
echo "   Inflation min : " . formatPourcentage($stats['min']) . "\n";
echo "   Nombre de mois : {$stats['nb_mois']}\n\n";

// Test 6: Fonctions de validation
echo "6. Test validation :\n";
echo "   Année 2015 valide : " . (validerAnnee(2015) ? 'OUI' : 'NON') . "\n";
echo "   Année 2030 valide : " . (validerAnnee(2030) ? 'OUI' : 'NON') . "\n";
echo "   Mois 8 valide : " . (validerMois(8) ? 'OUI' : 'NON') . "\n";
echo "   Mois 13 valide : " . (validerMois(13) ? 'OUI' : 'NON') . "\n";
echo "   Montant 1000 valide : " . (validerMontant(1000) ? 'OUI' : 'NON') . "\n\n";

echo "✅ TOUS LES TESTS TERMINÉS\n";

$conn->close();
?>