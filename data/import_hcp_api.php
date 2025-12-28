<?php
/**
 * Script d'import avancé des données HCP
 * Supporte : CSV, Excel, API data.gov.ma
 *
 * Utilisation :
 * php data/import_hcp_api.php --source=csv --file=data.csv
 * php data/import_hcp_api.php --source=api
 * php data/import_hcp_api.php --source=url --url=https://...
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class HCPImporterAPI {
    private $db;
    private $log_file;
    private $errors = [];
    private $warnings = [];
    private $stats = [
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    ];

    // Configuration API data.gov.ma (corrigée)
    private $api_config = [
        // Nouvelle API CKAN de data.gov.ma
        'base_url' => 'https://www.data.gov.ma/data/api/3/action',
        'search_url' => 'https://www.data.gov.ma/data/api/3/action/package_search',
        'package_url' => 'https://www.data.gov.ma/data/api/3/action/package_show',

        // URLs directes connues pour HCP
        'hcp_direct' => [
            'ipc_base' => 'https://www.hcp.ma/downloads/IPC-Indice-des-prix-a-la-consommation_t12173.html',
            'conjoncture' => 'https://www.hcp.ma/Indices-des-prix-a-la-consommation-IPC_r348.html'
        ],

        // Mots-clés de recherche
        'search_terms' => [
            'ipc', 'inflation', 'prix consommation', 'hcp'
        ]
    ];

    public function __construct($database) {
        $this->db = $database;
        $this->log_file = __DIR__ . '/../logs/import_' . date('Y-m-d_His') . '.log';
    }

    /**
     * Logger les messages
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp][$level] $message\n";

        file_put_contents($this->log_file, $log_entry, FILE_APPEND);
        echo $log_entry;
    }

    /**
     * ==========================================
     * IMPORT DEPUIS API DATA.GOV.MA
     * ==========================================
     */
    public function importFromAPI() {
        $this->log("🌐 Import depuis API data.gov.ma / HCP");

        // Étape 1 : Tenter via API data.gov.ma
        $this->log("Recherche des datasets HCP via API...");
        $datasets = $this->searchDatasets('HCP IPC inflation');

        if (empty($datasets)) {
            $this->log("⚠️ Aucun dataset trouvé via API", 'WARNING');
            $this->log("ℹ️  Conseil : Vérifiez manuellement https://www.data.gov.ma/");

            // Message d'aide pour import manuel
            echo "\n";
            echo "📌 IMPORT MANUEL RECOMMANDÉ :\n";
            echo "  1. Visitez : https://www.hcp.ma/downloads/IPC-Indice-des-prix-a-la-consommation_t12173.html\n";
            echo "  2. Téléchargez le fichier CSV/Excel le plus récent\n";
            echo "  3. Exécutez : php data/import_hcp_api.php --source=csv --file=VOTRE_FICHIER.csv\n";
            echo "\n";

            return false;
        }

        $this->log("✅ Datasets trouvés : " . count($datasets));

        // Étape 2 : Pour chaque dataset, récupérer les données
        foreach ($datasets as $dataset) {
            $this->log("📦 Dataset : " . $dataset['title']);

            // Si c'est un dataset direct (fallback)
            if (isset($dataset['resources'])) {
                $resources = $dataset['resources'];
            } else {
                // Récupérer via API
                $resources = $this->getDatasetResources($dataset['id']);
            }

            foreach ($resources as $resource) {
                $this->log("📄 Ressource : " . $resource['name']);

                if (isset($resource['url'])) {
                    $format = strtolower($resource['format'] ?? 'csv');
                    $this->importFromURL($resource['url'], $format);
                }
            }
        }

        return true;
    }

    /**
     * Rechercher des datasets sur data.gov.ma
     */
    private function searchDatasets($query) {
        // Essayer la nouvelle URL
        $url = 'https://www.data.gov.ma/data/api/3/action/package_search';
        $params = [
            'q' => $query,
            'rows' => 20,
            'fq' => 'organization:hcp' // Filtrer par organisation HCP
        ];

        $url .= '?' . http_build_query($params);

        $this->log("🔍 API Request: $url");

        $response = $this->makeAPIRequest($url);

        if (!$response) {
            $this->log("⚠️ API indisponible, tentative de scraping direct...", 'WARNING');
            return $this->fallbackScrapHCP();
        }

        if (!isset($response['success']) || !$response['success']) {
            $this->log("❌ Erreur API response", 'ERROR');
            return $this->fallbackScrapHCP();
        }

        if (!isset($response['result']['results'])) {
            $this->log("⚠️ Aucun résultat trouvé", 'WARNING');
            return [];
        }

        return $response['result']['results'];
    }

    /**
     * Récupérer les ressources d'un dataset
     */
    private function getDatasetResources($dataset_id) {
        $url = $this->api_config['base_url'] . '/package_show';
        $params = ['id' => $dataset_id];

        $url .= '?' . http_build_query($params);

        $response = $this->makeAPIRequest($url);

        if (!$response || !isset($response['result']['resources'])) {
            return [];
        }

        return $response['result']['resources'];
    }

    /**
     * Faire une requête API
     */
    private function makeAPIRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Maroc-Inflation/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            $this->log("Erreur HTTP : $http_code", 'ERROR');
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Fallback : Scraper directement depuis HCP si API échoue
     */
    private function fallbackScrapHCP() {
        $this->log("🕷️ Tentative de récupération directe depuis HCP...");

        // URLs connues du HCP pour l'IPC
        $hcp_urls = [
            'https://www.hcp.ma/downloads/IPC-Indice-des-prix-a-la-consommation_t12173.html',
            'https://www.hcp.ma/Indices-des-prix-a-la-consommation-IPC_r348.html'
        ];

        $datasets = [];

        foreach ($hcp_urls as $url) {
            $this->log("Vérification de : $url");

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $html = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200 && $html) {
                // Rechercher les liens vers fichiers CSV/Excel
                preg_match_all('/<a[^>]+href=["\'](.*?\.(csv|xlsx|xls))["\']/i', $html, $matches);

                if (!empty($matches[1])) {
                    foreach ($matches[1] as $index => $file_url) {
                        // S'assurer que l'URL est absolue
                        if (!preg_match('/^https?:\/\//', $file_url)) {
                            $file_url = 'https://www.hcp.ma' . $file_url;
                        }

                        $datasets[] = [
                            'title' => 'IPC HCP - Fichier ' . ($index + 1),
                            'id' => 'hcp_direct_' . $index,
                            'resources' => [[
                                'name' => basename($file_url),
                                'url' => $file_url,
                                'format' => strtoupper($matches[2][$index])
                            ]]
                        ];
                    }

                    $this->log("✅ Trouvé " . count($matches[1]) . " fichiers sur HCP");
                }
            }
        }

        return $datasets;
    }

    /**
     * ==========================================
     * IMPORT DEPUIS URL
     * ==========================================
     */
    public function importFromURL($url, $format = 'csv') {
        $this->log("📥 Téléchargement depuis : $url");

        $destination = __DIR__ . '/csv/download_' . date('YmdHis') . '.' . strtolower($format);

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $data = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200) {
                throw new Exception("Erreur HTTP : $http_code");
            }

            file_put_contents($destination, $data);
            $this->log("✅ Fichier téléchargé : $destination");

            // Import selon le format
            if (strtolower($format) === 'csv') {
                return $this->importFromCSV($destination);
            } elseif (strtolower($format) === 'xlsx' || strtolower($format) === 'xls') {
                $this->log("Format Excel détecté, conversion nécessaire", 'INFO');
                // Note: Pour Excel, il faudrait utiliser une bibliothèque comme PhpSpreadsheet
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->log("❌ Erreur téléchargement : " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * ==========================================
     * IMPORT DEPUIS CSV
     * ==========================================
     */
    public function importFromCSV($filepath, $delimiter = ',') {
        $this->log("📄 Import CSV : $filepath");

        if (!file_exists($filepath)) {
            $this->log("❌ Fichier introuvable : $filepath", 'ERROR');
            return false;
        }

        $handle = fopen($filepath, 'r');

        // Détecter le délimiteur automatiquement
        $first_line = fgets($handle);
        rewind($handle);

        if (substr_count($first_line, ';') > substr_count($first_line, ',')) {
            $delimiter = ';';
            $this->log("Délimiteur détecté : point-virgule");
        }

        // Lire l'en-tête
        $header = fgetcsv($handle, 1000, $delimiter);

        // Nettoyer les noms de colonnes
        $header = array_map(function($col) {
            return trim(strtolower($col));
        }, $header);

        $this->log("En-tête CSV : " . implode(', ', $header));

        // Mapper les colonnes
        $column_map = $this->detectColumns($header);

        if (!$column_map) {
            $this->log("❌ Impossible de détecter les colonnes nécessaires", 'ERROR');
            fclose($handle);
            return false;
        }

        $line_number = 1;

        while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
            $line_number++;

            try {
                // Extraire les données
                $annee = $this->extractValue($data, $column_map['annee']);
                $mois = $this->extractValue($data, $column_map['mois']);
                $ipc = $this->extractValue($data, $column_map['ipc']);
                $inf_mensuelle = $this->extractValue($data, $column_map['inflation_mensuelle']);
                $inf_annuelle = $this->extractValue($data, $column_map['inflation_annuelle']);

                // Validation
                if (!$this->validateData($annee, $mois, $ipc)) {
                    $this->stats['skipped']++;
                    $this->warnings[] = "Ligne $line_number : données invalides";
                    continue;
                }

                // Nettoyer
                $annee = intval($annee);
                $mois = intval($mois);
                $ipc = $this->cleanNumber($ipc);
                $inf_mensuelle = $this->cleanNumber($inf_mensuelle);
                $inf_annuelle = $this->cleanNumber($inf_annuelle);

                // Calculer inflation si manquante
                if ($inf_annuelle === null && $ipc) {
                    $inf_annuelle = $this->calculateInflation($annee, $mois, $ipc);
                }

                // Insérer ou mettre à jour
                if ($this->checkExists($annee, $mois)) {
                    $this->updateData($annee, $mois, $ipc, $inf_mensuelle, $inf_annuelle);
                    $this->stats['updated']++;
                } else {
                    $this->insertData($annee, $mois, $ipc, $inf_mensuelle, $inf_annuelle);
                    $this->stats['inserted']++;
                }

            } catch (Exception $e) {
                $this->stats['errors']++;
                $this->errors[] = "Ligne $line_number : " . $e->getMessage();
            }
        }

        fclose($handle);
        $this->log("✅ Import CSV terminé");

        return true;
    }

    /**
     * Détecter les colonnes dans le CSV
     */
    private function detectColumns($header) {
        $map = [
            'annee' => null,
            'mois' => null,
            'ipc' => null,
            'inflation_mensuelle' => null,
            'inflation_annuelle' => null
        ];

        foreach ($header as $index => $col) {
            // Détection flexible
            if (preg_match('/ann[eé]e|year/i', $col)) {
                $map['annee'] = $index;
            } elseif (preg_match('/mois|month/i', $col)) {
                $map['mois'] = $index;
            } elseif (preg_match('/ipc|indice|index/i', $col)) {
                $map['ipc'] = $index;
            } elseif (preg_match('/mensuel|monthly/i', $col)) {
                $map['inflation_mensuelle'] = $index;
            } elseif (preg_match('/annuel|yearly|annual/i', $col)) {
                $map['inflation_annuelle'] = $index;
            }
        }

        // Vérifier colonnes essentielles
        if ($map['annee'] === null || $map['mois'] === null || $map['ipc'] === null) {
            return null;
        }

        return $map;
    }

    /**
     * Extraire une valeur
     */
    private function extractValue($data, $index) {
        if ($index === null) return null;
        return isset($data[$index]) ? trim($data[$index]) : null;
    }

    /**
     * Nettoyer un nombre
     */
    private function cleanNumber($value) {
        if ($value === null || $value === '') return null;
        $value = str_replace([' ', ','], ['', '.'], $value);
        return floatval($value);
    }

    /**
     * Valider les données
     */
    private function validateData($annee, $mois, $ipc) {
        if (!$annee || !$mois || !$ipc) return false;

        $annee = intval($annee);
        $mois = intval($mois);

        if ($annee < 2000 || $annee > 2100) return false;
        if ($mois < 1 || $mois > 12) return false;

        return true;
    }

    /**
     * Calculer l'inflation annuelle
     */
    private function calculateInflation($annee, $mois, $ipc_actuel) {
        $annee_precedente = $annee - 1;

        $sql = "SELECT valeur_ipc FROM ipc_mensuel WHERE annee = ? AND mois = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $annee_precedente, $mois);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $ipc_precedent = floatval($row['valeur_ipc']);
            return (($ipc_actuel - $ipc_precedent) / $ipc_precedent) * 100;
        }

        return null;
    }

    /**
     * Vérifier si existe
     */
    private function checkExists($annee, $mois) {
        $sql = "SELECT COUNT(*) as count FROM ipc_mensuel WHERE annee = ? AND mois = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $annee, $mois);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    /**
     * Insérer des données
     */
    private function insertData($annee, $mois, $ipc, $inf_mens, $inf_ann) {
        $sql = "INSERT INTO ipc_mensuel (annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiddd', $annee, $mois, $ipc, $inf_mens, $inf_ann);
        $stmt->execute();
    }

    /**
     * Mettre à jour des données
     */
    private function updateData($annee, $mois, $ipc, $inf_mens, $inf_ann) {
        $sql = "UPDATE ipc_mensuel
                SET valeur_ipc = ?, inflation_mensuelle = ?, inflation_annuelle = ?
                WHERE annee = ? AND mois = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('dddii', $ipc, $inf_mens, $inf_ann, $annee, $mois);
        $stmt->execute();
    }

    /**
     * Rapport final
     */
    public function showReport() {
        echo "\n";
        echo "╔════════════════════════════════════════╗\n";
        echo "║      RAPPORT D'IMPORT HCP - API        ║\n";
        echo "╚════════════════════════════════════════╝\n";
        echo "\n";

        echo "📊 STATISTIQUES :\n";
        echo "  ✅ Insérés    : " . $this->stats['inserted'] . "\n";
        echo "  🔄 Mis à jour : " . $this->stats['updated'] . "\n";
        echo "  ⏭️  Ignorés    : " . $this->stats['skipped'] . "\n";
        echo "  ❌ Erreurs    : " . $this->stats['errors'] . "\n";
        echo "\n";

        if (!empty($this->warnings)) {
            echo "⚠️  AVERTISSEMENTS (" . count($this->warnings) . ") :\n";
            foreach (array_slice($this->warnings, 0, 3) as $warning) {
                echo "  - $warning\n";
            }
            echo "\n";
        }

        if (!empty($this->errors)) {
            echo "❌ ERREURS (" . count($this->errors) . ") :\n";
            foreach (array_slice($this->errors, 0, 3) as $error) {
                echo "  - $error\n";
            }
            echo "\n";
        }

        echo "📄 Log : " . basename($this->log_file) . "\n\n";
    }
}

// ==========================================
// EXÉCUTION
// ==========================================

$options = getopt('', ['source:', 'file:', 'url:', 'delimiter:']);

$database = new Database();
$conn = $database->connect();
$importer = new HCPImporterAPI($conn);

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║  IMPORT HCP - API DATA.GOV.MA + CSV    ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

$success = false;

if (isset($options['source'])) {
    switch ($options['source']) {
        case 'api':
            $success = $importer->importFromAPI();
            break;

        case 'csv':
            if (isset($options['file'])) {
                $delimiter = $options['delimiter'] ?? ',';
                $success = $importer->importFromCSV($options['file'], $delimiter);
            } else {
                echo "❌ --file requis avec --source=csv\n";
            }
            break;

        case 'url':
            if (isset($options['url'])) {
                $format = pathinfo($options['url'], PATHINFO_EXTENSION);
                $success = $importer->importFromURL($options['url'], $format);
            } else {
                echo "❌ --url requis avec --source=url\n";
            }
            break;
    }
} else {
    echo "ℹ️  UTILISATION :\n\n";
    echo "  # Import depuis API data.gov.ma (automatique)\n";
    echo "  php data/import_hcp_api.php --source=api\n\n";
    echo "  # Import depuis CSV local\n";
    echo "  php data/import_hcp_api.php --source=csv --file=data/csv/ipc.csv\n\n";
    echo "  # Import depuis URL\n";
    echo "  php data/import_hcp_api.php --source=url --url=https://...\n\n";
}

if ($success) {
    $importer->showReport();
}

$conn->close();
?>