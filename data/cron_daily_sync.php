<?php
/**
 * Synchronisation quotidienne automatique
 * Cron : 0 2 * * * php /path/to/cron_daily_sync.php
 */

require_once __DIR__ . '/../includes/config.php';

$log_file = __DIR__ . '/../logs/sync.log';
$webhook_url = $_ENV['SYNC_WEBHOOK_URL'] ?? getenv('SYNC_WEBHOOK_URL') ?: null;

/**
 * Écrire dans la sortie + le fichier de logs.
 */
function logSync(string $message): void {
    global $log_file;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    echo $message . PHP_EOL;
    file_put_contents($log_file, $line, FILE_APPEND);
}

/**
 * Exécuter une commande et tracer le résultat.
 */
function runJob(string $label, string $command): bool {
    logSync("→ {$label}...");
    $output = [];
    exec($command, $output, $return);
    $status = $return === 0 ? '✅ OK' : "❌ Erreur (code {$return})";
    logSync("   {$status}");
    return $return === 0;
}

/**
 * Envoyer une notification (Slack/webhook) en cas d'échec.
 */
function sendFailureNotification(array $statuses, ?string $webhook_url): void {
    if (!$webhook_url) {
        logSync('ℹ️  Aucun webhook configuré (SYNC_WEBHOOK_URL), notification non envoyée.');
        return;
    }

    $failed = array_filter($statuses, fn($job) => $job['success'] === false);
    if (empty($failed)) {
        return; // rien à notifier
    }

    $lines = array_map(function($job) {
        return ($job['success'] ? '✅' : '❌') . ' ' . $job['label'] . ($job['message'] ? ' - ' . $job['message'] : '');
    }, $statuses);

    $payload = [
        'text' => "[Maroc Inflation] Sync échouée le " . date('Y-m-d H:i:s') . "\n" . implode("\n", $lines),
    ];

    $body = json_encode($payload);

    if (function_exists('curl_init')) {
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        logSync("Notification webhook envoyée (HTTP {$http_code})");
    } else {
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $body
            ]
        ];
        file_get_contents($webhook_url, false, stream_context_create($opts));
        logSync("Notification webhook envoyée (stream)");
    }
}

logSync("\n═══════════════════════════════════════════");
logSync("  SYNC QUOTIDIENNE - " . date('Y-m-d H:i:s'));
logSync("═══════════════════════════════════════════");

$dayOfWeek = date('N');
$statuses = [];

// Bank Al-Maghrib (Lun-Ven)
if ($dayOfWeek < 6) {
    $statuses[] = [
        'label' => 'Bank Al-Maghrib',
        'success' => runJob('Bank Al-Maghrib', 'php ' . __DIR__ . '/import_bank_al_maghrib.php'),
        'message' => ''
    ];
} else {
    logSync("⏭️  Bank Al-Maghrib (week-end)");
}

// HCP (quotidien - vérifie MAJ)
$statuses[] = [
    'label' => 'HCP (CKAN)',
    'success' => runJob('HCP (CKAN)', 'php ' . __DIR__ . '/import_hcp_ckan.php'),
    'message' => ''
];

// World Bank (lundi uniquement)
if ($dayOfWeek === 1) {
    $statuses[] = [
        'label' => 'World Bank (hebdo)',
        'success' => runJob('World Bank (hebdo)', 'php ' . __DIR__ . '/import_world_bank.php'),
        'message' => ''
    ];
}

// News Scraping (quotidien)
$statuses[] = [
    'label' => 'News HCP',
    'success' => runJob('News HCP', 'php ' . __DIR__ . '/scrape_news_hcp.php'),
    'message' => ''
];

$statuses[] = [
    'label' => 'News Bank Al-Maghrib',
    'success' => runJob('News Bank Al-Maghrib', 'php ' . __DIR__ . '/scrape_news_bam.php'),
    'message' => ''
];

// Calcul Prévisions (lundi uniquement - après import des données)
if ($dayOfWeek === 1) {
    $statuses[] = [
        'label' => 'Calcul Prévisions',
        'success' => runJob('Calcul Prévisions', 'php ' . __DIR__ . '/calculate_previsions.php'),
        'message' => ''
    ];
}

logSync("═══════════════════════════════════════════");
logSync("  FIN - " . date('H:i:s'));
logSync("═══════════════════════════════════════════\n");

$hasFailure = array_reduce($statuses, fn($carry, $job) => $carry || ($job['success'] === false), false);
if ($hasFailure) {
    sendFailureNotification($statuses, $webhook_url);
}
?>
