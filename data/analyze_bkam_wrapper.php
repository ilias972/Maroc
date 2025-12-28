<?php
/**
 * Analyse du wrapper GitHub Bank Al-Maghrib
 * Source: https://github.com/imadarchid/bkam-wrapper
 */

echo "\n╔═══════════════════════════════════════════╗\n";
echo "║   ANALYSE WRAPPER BANK AL-MAGHRIB        ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

// Informations extraites du wrapper GitHub
$endpoints = [
    'cours_bbe' => [
        'url' => 'https://apihelpdesk.centralbankofmorocco.ma/BAM/CoursChange/api/CoursChange/GetCoursBBE',
        'method' => 'POST',
        'description' => 'Cours des billets de banque étrangers',
        'params' => ['dateValue' => 'YYYY-MM-DD'],
        'product' => 'Marché des changes'
    ],
    'cours_virement' => [
        'url' => 'https://apihelpdesk.centralbankofmorocco.ma/BAM/CoursChange/api/CoursChange/GetCoursVirement',
        'method' => 'POST',
        'description' => 'Cours des virements',
        'params' => ['dateValue' => 'YYYY-MM-DD'],
        'product' => 'Marché des changes'
    ],
    'marche_adjud_bt' => [
        'url' => 'https://apihelpdesk.centralbankofmorocco.ma/BAM/MarcheMonetaire/api/MarcheMonetaire/GetMarcheAdjudBT',
        'method' => 'POST',
        'description' => 'Marché des adjudications Bons du Trésor',
        'params' => ['dateValue' => 'YYYY-MM-DD'],
        'product' => 'Marché monétaire'
    ],
];

echo "📋 Endpoints identifiés :\n\n";

foreach ($endpoints as $name => $info) {
    echo "→ $name\n";
    echo "  URL : " . $info['url'] . "\n";
    echo "  Méthode : " . $info['method'] . "\n";
    echo "  Description : " . $info['description'] . "\n";
    echo "  Produit : " . $info['product'] . "\n";
    echo "\n";
}

echo "🔑 Authentification :\n";
echo "  Header : Ocp-Apim-Subscription-Key\n";
echo "  Clé : a53824b98185450f9adb4e637194c7a0\n\n";

echo "💡 Structure requête POST (exemple) :\n";
echo json_encode(['dateValue' => date('Y-m-d')], JSON_PRETTY_PRINT) . "\n\n";

echo "✅ Informations extraites du wrapper GitHub prêtes pour implémentation\n\n";
?>