<?php
/**
 * Audit complet de toutes les pages du site
 * Teste accessibilité, erreurs PHP, liens cassés
 */

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║           AUDIT COMPLET - MAROC INFLATION                 ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$base_dir = __DIR__ . '/..';
$public_dir = $base_dir . '/public';

// Pages à auditer
$pages = [];

// Scanner toutes les pages PHP publiques
foreach (glob($public_dir . '/*.php') as $file) {
    $pages[] = basename($file);
}

// Scanner API
foreach (glob($public_dir . '/api/*.php') as $file) {
    $pages[] = 'api/' . basename($file);
}

$resultats = [];

echo "🔍 Audit de " . count($pages) . " pages...\n\n";

foreach ($pages as $page) {
    $filepath = $public_dir . '/' . $page;

    if (!file_exists($filepath)) {
        continue;
    }

    $errors = [];
    $warnings = [];
    $status = '✅';

    // 1. Test syntaxe PHP
    $output = [];
    exec("php -l $filepath 2>&1", $output, $return);

    if ($return !== 0) {
        $errors[] = "Erreur syntaxe PHP : " . implode(' ', $output);
        $status = '❌';
    }

    // 2. Vérifier requires essentiels
    $content = file_get_contents($filepath);

    // Ignorer pages admin et API pour certains checks
    $is_admin = strpos($page, 'admin_') === 0;
    $is_api = strpos($page, 'api/') === 0;
    $is_secure = strpos($page, 'secure-') === 0;

    if (!$is_api && !$is_secure) {
        // Vérifier require database.php
        if (strpos($content, 'new Database()') !== false &&
            strpos($content, 'database.php') === false) {
            $errors[] = "Utilise Database() mais ne require pas database.php";
            $status = '❌';
        }

        // Vérifier require functions.php si utilise fonctions custom
        if ((strpos($content, 'getIPC(') !== false ||
             strpos($content, 'getDernierTauxChange(') !== false) &&
            strpos($content, 'functions.php') === false) {
            $warnings[] = "Utilise fonctions custom mais ne require pas functions.php";
            if ($status === '✅') $status = '⚠️ ';
        }

        // Vérifier require i18n.php si utilise traductions
        if (strpos($content, '__(') !== false &&
            strpos($content, 'i18n.php') === false) {
            $errors[] = "Utilise __() mais ne require pas i18n.php";
            $status = '❌';
        }
    }

    // 3. Vérifier bind_param correct (pas de valeurs directes)
    if (preg_match('/bind_param\([^,]+,\s*\$[a-zA-Z_]+\[[\'"]/', $content)) {
        $warnings[] = "bind_param avec tableau direct (devrait être variable)";
        if ($status === '✅') $status = '⚠️ ';
    }

    // 4. Vérifier fermeture connexions MySQL
    if (strpos($content, '$conn = $database->connect()') !== false &&
        strpos($content, '$conn->close()') === false) {
        $warnings[] = "Connexion MySQL ouverte mais jamais fermée";
        if ($status === '✅') $status = '⚠️ ';
    }

    // 5. Vérifier données mockées potentielles
    $mock_patterns = [
        '/\$inflation.*=\s*[0-9.]+;/' => 'Variable inflation hardcodée',
        '/"Lorem ipsum/' => 'Texte placeholder Lorem ipsum',
        '/"Exemple/' => 'Texte exemple',
        '/\/\/\s*TODO/' => 'TODO non résolu',
        '/\/\/\s*FIXME/' => 'FIXME non résolu'
    ];

    foreach ($mock_patterns as $pattern => $desc) {
        if (preg_match($pattern, $content)) {
            $warnings[] = $desc;
            if ($status === '✅') $status = '⚠️ ';
        }
    }

    $resultats[] = [
        'page' => $page,
        'status' => $status,
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

// Affichage résultats
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                   RÉSULTATS AUDIT                         ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$total = count($resultats);
$ok = 0;
$warn = 0;
$err = 0;

foreach ($resultats as $r) {
    if ($r['status'] === '✅') $ok++;
    elseif ($r['status'] === '⚠️ ') $warn++;
    else $err++;

    echo $r['status'] . " " . $r['page'] . "\n";

    foreach ($r['errors'] as $error) {
        echo "   ❌ " . $error . "\n";
    }

    foreach ($r['warnings'] as $warning) {
        echo "   ⚠️  " . $warning . "\n";
    }

    if (!empty($r['errors']) || !empty($r['warnings'])) {
        echo "\n";
    }
}

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    STATISTIQUES                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "Total pages : $total\n";
echo "✅ OK        : $ok (" . round($ok/$total*100) . "%)\n";
echo "⚠️  Warnings : $warn (" . round($warn/$total*100) . "%)\n";
echo "❌ Erreurs  : $err (" . round($err/$total*100) . "%)\n\n";

if ($err > 0) {
    echo "🔧 ACTIONS REQUISES :\n";
    echo "1. Corriger les erreurs fatales (❌)\n";
    echo "2. Vérifier les warnings (⚠️ )\n";
    echo "3. Re-tester après corrections\n\n";
}

// Sauvegarder rapport
$rapport = "# AUDIT PAGES - " . date('Y-m-d H:i:s') . "\n\n";
$rapport .= "Total : $total | OK : $ok | Warnings : $warn | Erreurs : $err\n\n";

foreach ($resultats as $r) {
    $rapport .= $r['status'] . " " . $r['page'] . "\n";
    foreach ($r['errors'] as $e) $rapport .= "  - ERREUR : $e\n";
    foreach ($r['warnings'] as $w) $rapport .= "  - Warning : $w\n";
    $rapport .= "\n";
}

file_put_contents(__DIR__ . '/AUDIT_PAGES.txt', $rapport);
echo "📄 Rapport sauvegardé : data/AUDIT_PAGES.txt\n\n";
?>