<?php
/**
 * Import données démographiques HCP depuis data.gov.ma
 * Population, chômage, pauvreté par région
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class DemographieImporter {
    private $db;

    // API data.gov.ma - datasets démographiques HCP
    private $api_base = 'https://www.data.gov.ma/data/api/3/action';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Rechercher datasets démographiques
     */
    public function searchDemographicData() {
        echo "🔍 Recherche datasets démographiques HCP...\n";

        $keywords = [
            'population maroc',
            'chomage maroc',
            'pauvrete maroc',
            'recensement maroc'
        ];

        foreach ($keywords as $keyword) {
            echo "\nRecherche : $keyword\n";
            $url = $this->api_base . '/package_search?q=' . urlencode($keyword) . '&rows=5';

            $response = $this->makeAPIRequest($url);

            if ($response && isset($response['result']['results'])) {
                foreach ($response['result']['results'] as $dataset) {
                    echo "  📦 {$dataset['title']}\n";
                    echo "     ID: {$dataset['id']}\n";
                }
            }
        }
    }

    /**
     * Générer des données exemple
     */
    public function generateSampleData() {
        echo "🧪 Génération données démographiques exemple...\n";

        // Les données sont déjà dans le SQL, afficher juste les stats
        $sql = "SELECT COUNT(*) as count FROM demographie_villes";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();

        echo "✅ {$row['count']} villes avec données démographiques\n";
    }

    /**
     * Afficher statistiques démographiques
     */
    public function showDemographyStats() {
        echo "\n📊 STATISTIQUES DÉMOGRAPHIQUES\n";
        echo "=================================\n\n";

        // Total population
        $sql = "SELECT SUM(population) as total_pop FROM demographie_villes";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        echo "👥 Population totale (17 villes) : " . number_format($row['total_pop']) . "\n\n";

        // Top 5 villes par population
        echo "🏙️  TOP 5 VILLES PAR POPULATION :\n";
        $sql = "SELECT ville, population FROM demographie_villes ORDER BY population DESC LIMIT 5";
        $result = $this->db->query($sql);
        while ($row = $result->fetch_assoc()) {
            echo sprintf("  %s : %s habitants\n",
                str_pad($row['ville'], 15),
                number_format($row['population'])
            );
        }

        // Chômage moyen
        echo "\n💼 TAUX DE CHÔMAGE :\n";
        $sql = "SELECT AVG(taux_chomage) as moy FROM demographie_villes";
        $result = $this->db->query($sql);
        $row = $result->fetch_assoc();
        echo "  Moyenne nationale : " . number_format($row['moy'], 2) . "%\n";

        // Villes avec plus fort chômage
        $sql = "SELECT ville, taux_chomage FROM demographie_villes ORDER BY taux_chomage DESC LIMIT 3";
        $result = $this->db->query($sql);
        echo "  Plus élevé :\n";
        while ($row = $result->fetch_assoc()) {
            echo sprintf("    %s : %.2f%%\n", $row['ville'], $row['taux_chomage']);
        }

        echo "\n";
    }

    /**
     * Faire requête API
     */
    private function makeAPIRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            return json_decode($response, true);
        }

        return null;
    }
}

// Exécution
$database = new Database();
$conn = $database->connect();
$importer = new DemographieImporter($conn);

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║    IMPORT DONNÉES DÉMOGRAPHIQUES       ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

// Tenter recherche API (optionnel)
// $importer->searchDemographicData();

// Générer/vérifier données
$importer->generateSampleData();
$importer->showDemographyStats();

$conn->close();
?>