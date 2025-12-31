<?php
/**
 * Scraper automatique - Actualités HCP (Haut-Commissariat au Plan)
 * Source : https://www.hcp.ma/Communiques-de-presse_4.html
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class HCPNewsScraper {
    private $db;
    private $source_url = 'https://www.hcp.ma/Communiques-de-presse_4.html';
    private $base_url = 'https://www.hcp.ma';
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
     * Scraper la page des communiqués HCP
     */
    public function scrape() {
        echo "\n╔════════════════════════════════════════════╗\n";
        echo "║    SCRAPER ACTUALITÉS HCP                  ║\n";
        echo "╚════════════════════════════════════════════╝\n\n";

        echo "→ Récupération de la page HCP...\n";

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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Pour éviter erreurs SSL
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
     * Note: Cette fonction doit être adaptée à la structure réelle de la page HCP
     */
    private function parseArticles($html) {
        $articles = [];

        // Convertir en UTF-8 si nécessaire
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }

        // Méthode 1 : Parser avec DOMDocument (plus robuste)
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Pattern générique pour trouver les liens vers des communiqués
        // Adaptation nécessaire selon la structure réelle de la page
        $nodes = $xpath->query("//div[contains(@class, 'item') or contains(@class, 'article') or contains(@class, 'news')]//a[contains(@href, 'communique') or contains(@href, 'article')]");

        if ($nodes->length === 0) {
            // Fallback : chercher tous les liens dans la page principale
            $nodes = $xpath->query("//a[contains(@href, '.html')]");
        }

        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');
            $title = trim($node->textContent);

            // Filtrer les liens vides ou non pertinents
            if (empty($title) || strlen($title) < 10) continue;
            if (strpos($href, 'javascript') !== false) continue;

            // Construire URL complète
            $url = $this->makeAbsoluteUrl($href);

            // Extraire la date (pattern général : JJ/MM/AAAA)
            $date = $this->extractDate($title . ' ' . $href);

            // Catégoriser
            $category = $this->categorizeArticle($title);

            $articles[] = [
                'titre' => $this->cleanText($title),
                'description' => $this->extractDescription($title),
                'url_source' => $url,
                'date_publication' => $date ?: date('Y-m-d'),
                'categorie' => $category,
                'source' => 'HCP'
            ];

            // Limiter à 20 articles récents pour ne pas surcharger
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
     * Extraire la date depuis le texte
     */
    private function extractDate($text) {
        // Pattern : JJ/MM/AAAA ou JJ-MM-AAAA
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $matches)) {
            return $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        }

        // Pattern : AAAA-MM-JJ (ISO)
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Catégoriser l'article
     */
    private function categorizeArticle($title) {
        $title_lower = mb_strtolower($title);

        if (strpos($title_lower, 'inflation') !== false || strpos($title_lower, 'ipc') !== false || strpos($title_lower, 'prix') !== false) {
            return 'Inflation';
        }

        if (strpos($title_lower, 'pib') !== false || strpos($title_lower, 'croissance') !== false) {
            return 'Croissance';
        }

        if (strpos($title_lower, 'emploi') !== false || strpos($title_lower, 'chomage') !== false) {
            return 'Emploi';
        }

        if (strpos($title_lower, 'commerce') !== false || strpos($title_lower, 'import') !== false || strpos($title_lower, 'export') !== false) {
            return 'Commerce Extérieur';
        }

        return 'Économie Générale';
    }

    /**
     * Extraire une description (résumé du titre)
     */
    private function extractDescription($title) {
        // Pour l'instant, utiliser le titre comme description
        // Idéalement, visiter chaque page article pour extraire le contenu
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
     * Sauvegarder un article en base
     */
    private function saveArticle($article) {
        // Vérifier si existe déjà (par URL)
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
                (titre, description, source, categorie, date_publication, url_source, affiche, created_at)
                VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW())";

        $stmt = $this->db->prepare($sql);
        $titre = $article['titre'];
        $description = $article['description'];
        $source = $article['source'];
        $categorie = $article['categorie'];
        $date_pub = $article['date_publication'];
        $url = $article['url_source'];

        $stmt->bind_param('ssssss', $titre, $description, $source, $categorie, $date_pub, $url);

        if ($stmt->execute()) {
            echo "  ✅ Nouveau : " . mb_substr($article['titre'], 0, 60) . "...\n";
            $this->stats['new']++;
        } else {
            echo "  ❌ Erreur SQL : " . $this->db->error . "\n";
            $this->stats['errors']++;
        }
    }

    /**
     * Afficher les statistiques
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
    $scraper = new HCPNewsScraper($conn);

    $scraper->scrape();

    $conn->close();

    echo "✅ Scraping HCP terminé !\n\n";
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
