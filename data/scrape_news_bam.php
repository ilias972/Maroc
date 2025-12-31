<?php
/**
 * Scraper automatique - Actualités Bank Al-Maghrib
 * Source : https://www.bkam.ma/Communiques
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class BAMNewsScraper {
    private $db;
    private $source_url = 'https://www.bkam.ma/Communiques';
    private $base_url = 'https://www.bkam.ma';
    private $stats = [
        'found' => 0,
        'new' => 0,
        'existing' => 0,
        'errors' => 0
    ];

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Scraper la page des communiqués BAM
     */
    public function scrape() {
        echo "\n╔════════════════════════════════════════════╗\n";
        echo "║  SCRAPER ACTUALITÉS BANK AL-MAGHRIB        ║\n";
        echo "╚════════════════════════════════════════════╝\n\n";

        echo "→ Récupération de la page Bank Al-Maghrib...\n";

        $html = $this->fetchPage($this->source_url);

        if (!$html) {
            echo "  ❌ Impossible de récupérer la page\n";
            return false;
        }

        echo "  ✅ Page récupérée (" . strlen($html) . " octets)\n\n";

        // Parser les communiqués
        echo "→ Extraction des communiqués...\n";
        $articles = $this->parseArticles($html);

        echo "  ✅ " . count($articles) . " articles trouvés\n\n";
        $this->stats['found'] = count($articles);

        // Sauvegarder en base
        foreach ($articles as $article) {
            $this->saveArticle($article);
        }

        $this->showStats();
        return true;
    }

    /**
     * Récupérer le contenu HTML d'une page
     */
    private function fetchPage($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MarocInflationBot/1.0)');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            echo "  ⚠️  HTTP $http_code\n";
            return null;
        }

        return $response;
    }

    /**
     * Parser les articles depuis le HTML
     */
    private function parseArticles($html) {
        $articles = [];

        // Convertir en UTF-8 si nécessaire
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Chercher les liens vers des communiqués ou PDFs
        $nodes = $xpath->query("//div[contains(@class, 'communique') or contains(@class, 'item') or contains(@class, 'article')]//a | //a[contains(@href, '.pdf') or contains(@href, 'communique')]");

        if ($nodes->length === 0) {
            // Fallback
            $nodes = $xpath->query("//a[contains(@href, '.html') or contains(@href, '.pdf')]");
        }

        $seenUrls = [];

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            $title = trim($node->textContent);

            // Filtrer
            if (empty($title) || strlen($title) < 10) continue;
            if (strpos($href, 'javascript') !== false) continue;

            // Construire URL
            $url = $this->makeAbsoluteUrl($href);

            // Éviter doublons
            if (isset($seenUrls[$url])) continue;
            $seenUrls[$url] = true;

            // Détecter si c'est un PDF
            $is_pdf = (strpos($url, '.pdf') !== false);
            $url_rapport = $is_pdf ? $url : null;

            // Extraire date
            $date = $this->extractDate($title . ' ' . $href);

            // Catégorie
            $category = $this->categorizeArticle($title);

            $articles[] = [
                'titre' => $this->cleanText($title),
                'description' => $this->extractDescription($title),
                'url_source' => $url,
                'url_rapport' => $url_rapport,
                'date_publication' => $date ?: date('Y-m-d'),
                'categorie' => $category,
                'source' => 'Bank Al-Maghrib'
            ];

            // Limiter
            if (count($articles) >= 20) break;
        }

        return $articles;
    }

    /**
     * Convertir URL relative en absolue
     */
    private function makeAbsoluteUrl($url) {
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        if (strpos($url, '/') === 0) {
            return $this->base_url . $url;
        }

        return $this->base_url . '/' . $url;
    }

    /**
     * Extraire la date
     */
    private function extractDate($text) {
        // Pattern : JJ/MM/AAAA
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $matches)) {
            return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }

        // Pattern : AAAA-MM-JJ
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Catégoriser
     */
    private function categorizeArticle($title) {
        $title_lower = mb_strtolower($title);

        if (strpos($title_lower, 'taux directeur') !== false || strpos($title_lower, 'politique monétaire') !== false) {
            return 'Politique Monétaire';
        }

        if (strpos($title_lower, 'change') !== false || strpos($title_lower, 'devise') !== false) {
            return 'Taux de Change';
        }

        if (strpos($title_lower, 'inflation') !== false || strpos($title_lower, 'prix') !== false) {
            return 'Inflation';
        }

        if (strpos($title_lower, 'rapport') !== false || strpos($title_lower, 'bulletin') !== false) {
            return 'Publications';
        }

        if (strpos($title_lower, 'réunion') !== false || strpos($title_lower, 'conseil') !== false) {
            return 'Conseil de la Banque';
        }

        return 'Communiqués';
    }

    /**
     * Extraire description
     */
    private function extractDescription($title) {
        return mb_substr($title, 0, 250) . '...';
    }

    /**
     * Nettoyer le texte
     */
    private function cleanText($text) {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return $text;
    }

    /**
     * Sauvegarder un article
     */
    private function saveArticle($article) {
        // Vérifier existence
        $sql_check = "SELECT id FROM actualites_economiques WHERE url_source = ?";
        $stmt = $this->db->prepare($sql_check);
        $url_check = $article['url_source'];
        $stmt->bind_param('s', $url_check);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;

        if ($exists) {
            echo "  ⏭️  Existe déjà : " . mb_substr($article['titre'], 0, 60) . "...\n";
            $this->stats['existing']++;
            return;
        }

        // Insérer
        $sql = "INSERT INTO actualites_economiques
                (titre, description, source, categorie, date_publication, url_source, url_rapport, affiche, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, TRUE, NOW())";

        $stmt = $this->db->prepare($sql);
        $titre = $article['titre'];
        $description = $article['description'];
        $source = $article['source'];
        $categorie = $article['categorie'];
        $date_pub = $article['date_publication'];
        $url = $article['url_source'];
        $url_rapport = $article['url_rapport'];

        $stmt->bind_param('sssssss', $titre, $description, $source, $categorie, $date_pub, $url, $url_rapport);

        if ($stmt->execute()) {
            echo "  ✅ Nouveau : " . mb_substr($article['titre'], 0, 60) . "...\n";
            $this->stats['new']++;
        } else {
            echo "  ❌ Erreur SQL : " . $this->db->error . "\n";
            $this->stats['errors']++;
        }
    }

    /**
     * Statistiques
     */
    private function showStats() {
        echo "\n╔════════════════════════════════════════════╗\n";
        echo "║          STATISTIQUES SCRAPING             ║\n";
        echo "╚════════════════════════════════════════════╝\n\n";

        echo "📊 Articles trouvés : " . $this->stats['found'] . "\n";
        echo "✅ Nouveaux : " . $this->stats['new'] . "\n";
        echo "⏭️  Déjà existants : " . $this->stats['existing'] . "\n";
        echo "❌ Erreurs : " . $this->stats['errors'] . "\n\n";
    }
}

// Exécution
try {
    $database = new Database();
    $conn = $database->connect();
    $scraper = new BAMNewsScraper($conn);

    $scraper->scrape();

    $conn->close();

    echo "✅ Scraping Bank Al-Maghrib terminé !\n\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
