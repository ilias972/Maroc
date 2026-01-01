<?php
/**
 * DEBUG : Afficher contenu page HCP pour ajuster le regex
 */

$hcp_url = 'https://www.hcp.ma/L-Indice-des-prix-a-la-consommation-IPC-de-l-annee-2024_a4056.html';

echo "→ Récupération page HCP...\n";

$ch = curl_init($hcp_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
]);

$html = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n\n";

if ($html) {
    echo "========== CONTENU HTML (premiers 3000 caractères) ==========\n\n";
    echo substr($html, 0, 3000);
    echo "\n\n========== RECHERCHE PATTERNS ==========\n\n";

    // Chercher toutes les occurrences de nombres avec %
    preg_match_all('/(\d+[,.]?\d*)\s*%/', $html, $matches, PREG_SET_ORDER);
    echo "Nombres avec % trouvés : " . count($matches) . "\n";
    foreach (array_slice($matches, 0, 20) as $match) {
        echo "  - {$match[0]}\n";
    }

    echo "\n========== RECHERCHE NOMS DE VILLES ==========\n\n";

    $villes = ['Casablanca', 'Rabat', 'Fès', 'Marrakech', 'Tanger', 'Agadir',
               'Laâyoune', 'Laayoune', 'Dakhla', 'Guelmim', 'Safi', 'Meknès'];

    foreach ($villes as $ville) {
        if (stripos($html, $ville) !== false) {
            // Extraire contexte autour du nom de ville
            preg_match('/.{0,100}' . preg_quote($ville, '/') . '.{0,100}/ui', $html, $context);
            if ($context) {
                echo "$ville : " . trim($context[0]) . "\n\n";
            }
        }
    }

} else {
    echo "❌ Impossible de récupérer la page\n";
}
?>
