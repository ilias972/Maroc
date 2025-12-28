<?php
/**
 * Afficher le fil d'Ariane
 */
function displayBreadcrumb($page_title) {
    $breadcrumb_items = [
        ['name' => 'Accueil', 'url' => 'http://localhost:8000/index.php']
    ];

    // Ajouter la page actuelle
    if ($page_title !== 'Accueil') {
        $breadcrumb_items[] = ['name' => $page_title, 'url' => null];
    }

    // Générer le Schema
    SEO::generateBreadcrumb($breadcrumb_items);

    // Afficher le breadcrumb visuel
    echo '<nav aria-label="breadcrumb" class="mb-4">';
    echo '<ol class="breadcrumb">';

    foreach ($breadcrumb_items as $index => $item) {
        if ($index === count($breadcrumb_items) - 1) {
            echo '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($item['name']) . '</li>';
        } else {
            echo '<li class="breadcrumb-item"><a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['name']) . '</a></li>';
        }
    }

    echo '</ol>';
    echo '</nav>';
}
?>