<?php
/**
 * Inventaire complet du site Maroc Inflation
 * Analyse toutes les pages PHP et leur accessibilité
 */

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║          INVENTAIRE COMPLET - MAROC INFLATION             ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Chemins
$base_dir = __DIR__ . '/..';
$public_dir = $base_dir . '/public';
$api_dir = $public_dir . '/api';
$includes_dir = $base_dir . '/includes';

// Compteurs
$stats = [
    'total_pages' => 0,
    'pages_publiques' => 0,
    'pages_admin' => 0,
    'pages_api' => 0,
    'pages_accessibles' => 0,
    'pages_orphelines' => 0,
    'pages_avec_donnees_reelles' => 0,
    'pages_avec_donnees_mockees' => 0
];

// Inventaire des pages
$inventaire = [
    'publiques' => [],
    'admin' => [],
    'api' => [],
    'orphelines' => []
];

// Liens dans les menus
$liens_menu = [];

/**
 * Récupérer tous les fichiers PHP d'un dossier
 */
function getPhpFiles($dir) {
    if (!is_dir($dir)) {
        return [];
    }
    $files = glob($dir . '/*.php');
    return array_map('basename', $files);
}

/**
 * Analyser un fichier pour détecter les liens
 */
function analyserLiens($filepath) {
    if (!file_exists($filepath)) {
        return [];
    }

    $content = file_get_contents($filepath);
    $liens = [];

    // Trouver tous les href="xxx.php"
    preg_match_all('/href=["\']([^"\']*\.php)["\']/', $content, $matches);

    if (!empty($matches[1])) {
        foreach ($matches[1] as $lien) {
            // Nettoyer le lien (enlever ../, ./, etc.)
            $lien_clean = basename($lien);
            if (!in_array($lien_clean, $liens)) {
                $liens[] = $lien_clean;
            }
        }
    }

    return $liens;
}

/**
 * Vérifier si une page contient des données mockées
 */
function contientDonneesMockees($filepath) {
    $content = file_get_contents($filepath);

    // Patterns de données mockées
    $patterns = [
        '/\$.*=\s*\[\s*["\'].*["\']\s*=>\s*[0-9.]+/',  // Arrays avec données numériques
        '/\$.*inflation.*=\s*[0-9.]+;/',                // Variables inflation hardcodées
        '/\$.*taux.*=\s*[0-9.]+;/',                     // Variables taux hardcodées
        '/"Lorem ipsum/',                                 // Texte placeholder
        '/"Exemple/',                                     // Exemples
        '/\/\/\s*TODO/',                                  // TODOs
        '/\/\/\s*MOCK/',                                  // Commentaires MOCK
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }

    return false;
}

/**
 * Vérifier si une page se connecte à la base de données
 */
function seConnecteBDD($filepath) {
    $content = file_get_contents($filepath);

    return (
        strpos($content, 'new Database()') !== false ||
        strpos($content, '$conn->query') !== false ||
        strpos($content, '$stmt->execute') !== false ||
        strpos($content, 'mysqli_') !== false
    );
}

/**
 * Extraire le titre de la page
 */
function extraireTitre($filepath) {
    $content = file_get_contents($filepath);

    // Chercher $page_title = '...'
    if (preg_match('/\$page_title\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
        return $matches[1];
    }

    // Chercher <title>...</title>
    if (preg_match('/<title>([^<]+)<\/title>/', $content, $matches)) {
        return trim($matches[1]);
    }

    // Chercher <h1>...</h1>
    if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/', $content, $matches)) {
        return strip_tags($matches[1]);
    }

    return 'Sans titre';
}

// ═══════════════════════════════════════════════════════════
// COLLECTE DES DONNÉES
// ═══════════════════════════════════════════════════════════

echo "🔍 Analyse des fichiers...\n\n";

// 1. Pages publiques
$pages_publiques = getPhpFiles($public_dir);
echo "→ Pages publiques trouvées : " . count($pages_publiques) . "\n";

// 2. Pages API
$pages_api_files = getPhpFiles($api_dir);
echo "→ Pages API trouvées : " . count($pages_api_files) . "\n";

// 3. Analyser les menus de navigation
$fichiers_nav = [
    $includes_dir . '/header.php',
    $includes_dir . '/admin_header.php',
    $includes_dir . '/footer.php'
];

foreach ($fichiers_nav as $nav_file) {
    $liens = analyserLiens($nav_file);
    $liens_menu = array_merge($liens_menu, $liens);
}

$liens_menu = array_unique($liens_menu);
echo "→ Liens dans les menus : " . count($liens_menu) . "\n\n";

// ═══════════════════════════════════════════════════════════
// ANALYSE DÉTAILLÉE DE CHAQUE PAGE
// ═══════════════════════════════════════════════════════════

echo "📊 Analyse détaillée...\n\n";

foreach ($pages_publiques as $page) {
    $filepath = $public_dir . '/' . $page;

    // Déterminer le type
    $est_admin = (strpos($page, 'admin_') === 0 || strpos($page, 'secure-access') === 0);
    $est_api = false;
    $est_accessible = in_array($page, $liens_menu);
    $a_donnees_mockees = contientDonneesMockees($filepath);
    $connecte_bdd = seConnecteBDD($filepath);
    $titre = extraireTitre($filepath);

    $info = [
        'fichier' => $page,
        'titre' => $titre,
        'type' => $est_admin ? 'Admin' : 'Publique',
        'accessible' => $est_accessible,
        'dans_menu' => $est_accessible ? 'Oui' : 'Non',
        'connecte_bdd' => $connecte_bdd ? 'Oui' : 'Non',
        'donnees_mockees' => $a_donnees_mockees ? 'Oui' : 'Non',
        'statut' => $connecte_bdd ? ($a_donnees_mockees ? '⚠️ Mixte' : '✅ Réel') : '❌ Mock'
    ];

    // Catégoriser
    if ($est_admin) {
        $inventaire['admin'][] = $info;
        $stats['pages_admin']++;
    } else {
        $inventaire['publiques'][] = $info;
        $stats['pages_publiques']++;
    }

    if (!$est_accessible && !$est_admin) {
        $inventaire['orphelines'][] = $info;
        $stats['pages_orphelines']++;
    }

    if ($est_accessible) {
        $stats['pages_accessibles']++;
    }

    if ($connecte_bdd && !$a_donnees_mockees) {
        $stats['pages_avec_donnees_reelles']++;
    }

    if ($a_donnees_mockees) {
        $stats['pages_avec_donnees_mockees']++;
    }

    $stats['total_pages']++;
}

// Pages API
foreach ($pages_api_files as $api) {
    $filepath = $api_dir . '/' . $api;
    $titre = extraireTitre($filepath);
    $connecte_bdd = seConnecteBDD($filepath);

    $info = [
        'fichier' => 'api/' . $api,
        'titre' => $titre,
        'type' => 'API',
        'accessible' => 'N/A',
        'dans_menu' => 'Non',
        'connecte_bdd' => $connecte_bdd ? 'Oui' : 'Non',
        'donnees_mockees' => 'N/A',
        'statut' => $connecte_bdd ? '✅ Réel' : '❌ Mock'
    ];

    $inventaire['api'][] = $info;
    $stats['pages_api']++;
    $stats['total_pages']++;
}

// ═══════════════════════════════════════════════════════════
// GÉNÉRATION DU RAPPORT
// ═══════════════════════════════════════════════════════════

$rapport = "# 📊 INVENTAIRE COMPLET - MAROC INFLATION\n\n";
$rapport .= "**Date :** " . date('d/m/Y H:i:s') . "\n\n";

$rapport .= "## 📈 STATISTIQUES GLOBALES\n\n";
$rapport .= "| Métrique | Valeur |\n";
$rapport .= "|----------|--------|\n";
$rapport .= "| **Total pages** | " . $stats['total_pages'] . " |\n";
$rapport .= "| Pages publiques | " . $stats['pages_publiques'] . " |\n";
$rapport .= "| Pages admin | " . $stats['pages_admin'] . " |\n";
$rapport .= "| Pages API | " . $stats['pages_api'] . " |\n";
$rapport .= "| Pages accessibles (menu) | " . $stats['pages_accessibles'] . " |\n";
$rapport .= "| Pages orphelines | " . $stats['pages_orphelines'] . " |\n";
$rapport .= "| Pages données réelles | " . $stats['pages_avec_donnees_reelles'] . " |\n";
$rapport .= "| Pages données mockées | " . $stats['pages_avec_donnees_mockees'] . " |\n\n";

// Pages publiques
$rapport .= "## 🌐 PAGES PUBLIQUES (" . count($inventaire['publiques']) . ")\n\n";
$rapport .= "| Fichier | Titre | Menu | BDD | Mock | Statut |\n";
$rapport .= "|---------|-------|------|-----|------|--------|\n";
foreach ($inventaire['publiques'] as $page) {
    $rapport .= "| " . $page['fichier'] . " | " . $page['titre'] . " | " .
                $page['dans_menu'] . " | " . $page['connecte_bdd'] . " | " .
                $page['donnees_mockees'] . " | " . $page['statut'] . " |\n";
}

// Pages admin
$rapport .= "\n## 🔐 PAGES ADMIN (" . count($inventaire['admin']) . ")\n\n";
$rapport .= "| Fichier | Titre | BDD | Mock | Statut |\n";
$rapport .= "|---------|-------|-----|------|--------|\n";
foreach ($inventaire['admin'] as $page) {
    $rapport .= "| " . $page['fichier'] . " | " . $page['titre'] . " | " .
                $page['connecte_bdd'] . " | " . $page['donnees_mockees'] . " | " .
                $page['statut'] . " |\n";
}

// Pages API
$rapport .= "\n## 🔌 PAGES API (" . count($inventaire['api']) . ")\n\n";
$rapport .= "| Fichier | Titre | BDD | Statut |\n";
$rapport .= "|---------|-------|-----|--------|\n";
foreach ($inventaire['api'] as $page) {
    $rapport .= "| " . $page['fichier'] . " | " . $page['titre'] . " | " .
                $page['connecte_bdd'] . " | " . $page['statut'] . " |\n";
}

// Pages orphelines
if (!empty($inventaire['orphelines'])) {
    $rapport .= "\n## ⚠️ PAGES ORPHELINES (" . count($inventaire['orphelines']) . ")\n\n";
    $rapport .= "Ces pages existent mais ne sont PAS accessibles via les menus :\n\n";
    foreach ($inventaire['orphelines'] as $page) {
        $rapport .= "- **" . $page['fichier'] . "** : " . $page['titre'] . " " . $page['statut'] . "\n";
    }
}

// Recommandations
$rapport .= "\n## 💡 RECOMMANDATIONS\n\n";

if ($stats['pages_avec_donnees_mockees'] > 0) {
    $rapport .= "⚠️ **" . $stats['pages_avec_donnees_mockees'] . " pages contiennent encore des données mockées**\n\n";
    $rapport .= "Actions :\n";
    $rapport .= "1. Remplacer les données mockées par des vraies données\n";
    $rapport .= "2. Connecter les pages à la base de données\n";
    $rapport .= "3. Utiliser les APIs d'import (HCP, Bank Al-Maghrib, World Bank)\n\n";
}

if ($stats['pages_orphelines'] > 0) {
    $rapport .= "🔗 **" . $stats['pages_orphelines'] . " pages orphelines détectées**\n\n";
    $rapport .= "Actions :\n";
    $rapport .= "1. Ajouter des liens dans les menus si pertinent\n";
    $rapport .= "2. Supprimer si obsolètes\n\n";
}

$pct_reel = round(($stats['pages_avec_donnees_reelles'] / $stats['total_pages']) * 100);
$rapport .= "📊 **Progression données réelles : " . $pct_reel . "%**\n\n";

// Sauvegarder le rapport
$rapport_file = __DIR__ . '/INVENTAIRE_SITE.md';
file_put_contents($rapport_file, $rapport);

echo "\n🔍 PAGES PROBLÉMATIQUES DÉTECTÉES :\n\n";

foreach ($inventaire['publiques'] as $page) {
    if ($page['donnees_mockees'] === 'Oui' || $page['statut'] === '⚠️ Mixte') {
        echo "→ " . $page['fichier'] . "\n";
        echo "  Titre : " . $page['titre'] . "\n";
        echo "  BDD : " . $page['connecte_bdd'] . "\n";
        echo "  Mock : " . $page['donnees_mockees'] . "\n";
        echo "  Statut : " . $page['statut'] . "\n\n";
    }
}

foreach ($inventaire['admin'] as $page) {
    if ($page['donnees_mockees'] === 'Oui') {
        echo "→ [ADMIN] " . $page['fichier'] . "\n";
        echo "  Titre : " . $page['titre'] . "\n";
        echo "  Statut : " . $page['statut'] . "\n\n";
    }
}

echo "✅ Rapport généré : $rapport_file\n\n";
echo $rapport;

?>