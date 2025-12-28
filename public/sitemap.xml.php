<?php
header('Content-Type: application/xml; charset=utf-8');

require_once '../includes/config.php';
require_once '../includes/database.php';

$base_url = 'http://localhost:8000'; // À changer en production

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

    <!-- Page d'accueil -->
    <url>
        <loc><?= $base_url ?>/index.php</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
        <lastmod><?= date('Y-m-d') ?></lastmod>
    </url>

    <!-- Calculateur -->
    <url>
        <loc><?= $base_url ?>/calculateur_inflation.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Inflation actuelle -->
    <url>
        <loc><?= $base_url ?>/inflation_actuelle.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.9</priority>
        <lastmod><?= date('Y-m-d') ?></lastmod>
    </url>

    <!-- Historique -->
    <url>
        <loc><?= $base_url ?>/inflation_historique.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Comparaisons internationales -->
    <url>
        <loc><?= $base_url ?>/comparaisons_internationales.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Prévisions -->
    <url>
        <loc><?= $base_url ?>/previsions.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Inflation régionale -->
    <url>
        <loc><?= $base_url ?>/inflation_regionale.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- APIs (pour développeurs) -->
    <url>
        <loc><?= $base_url ?>/api/get_ipc.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc><?= $base_url ?>/api/get_inflation.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

</urlset>