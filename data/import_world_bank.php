<?php
/**
 * Import données inflation World Bank API
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class WorldBankImporter {

    private $db;
    private $api_base = 'https://api.worldbank.org/v2';

    private $countries = [
        'MA' => 'Maroc',
        'FR' => 'France',
        'ES' => 'Espagne',
        'DZ' => 'Algérie',
        'TN' => 'Tunisie',
        'DE' => 'Allemagne',
        'IT' => 'Italie',
        'PT' => 'Portugal'
    ];

    private $stats = [
        'imported' => 0,
        'updated' => 0,
        'errors' => 0
    ];

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Importer toutes les données inflation
     */
    public function importAll() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║      IMPORT WORLD BANK - INFLATION        ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        foreach ($this->countries as $code => $name) {
            echo "→ Import $name ($code)...\n";
            $this->importCountry($code, $name);
            echo "\n";
        }

        $this->showStats();
    }

    /**
     * Importer données d'un pays
     */
    private function importCountry($countryCode, $countryName) {
        $url = "$this->api_base/country/$countryCode/indicator/FP.CPI.TOTL.ZG?format=json&date=2020:2024&per_page=100";

        $data = $this->makeRequest($url);

        if (!$data || !isset($data[1])) {
            echo "  ❌ Erreur API\n";
            $this->stats['errors']++;
            return;
        }

        $records = $data[1];
        $imported = 0;

        foreach ($records as $record) {
            $year = $record['date'] ?? null;
            $value = $record['value'] ?? null;

            if ($year && $value !== null) {
                $this->saveInflation($countryCode, $year, $value);
                $imported++;
            }
        }

        echo "  ✅ $imported années importées\n";
        $this->stats['imported'] += $imported;
    }

    /**
     * Sauvegarder inflation en base
     */
    private function saveInflation($countryCode, $year, $value) {
        // Vérifier existence
        $sql_check = "SELECT id FROM inflation_internationale
                      WHERE code_pays = ? AND annee = ?";
        $stmt = $this->db->prepare($sql_check);
        $countryCode_check = $countryCode;
        $year_check = $year;
        $stmt->bind_param('si', $countryCode_check, $year_check);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;

        if ($exists) {
            // Mise à jour
            $sql = "UPDATE inflation_internationale
                    SET inflation_annuelle = ?
                    WHERE code_pays = ? AND annee = ?";
            $stmt = $this->db->prepare($sql);
            $value_upd = $value;
            $countryCode_upd = $countryCode;
            $year_upd = $year;
            $stmt->bind_param('dsi', $value_upd, $countryCode_upd, $year_upd);
            $stmt->execute();
            $this->stats['updated']++;
        } else {
            // Insertion
            $sql = "INSERT INTO inflation_internationale (code_pays, pays, annee, mois, inflation_annuelle, source)
                    VALUES (?, ?, ?, 12, ?, 'World Bank API')";
            $stmt = $this->db->prepare($sql);
            $countryCode_ins = $countryCode;
            $countryName_ins = $this->countries[$countryCode];
            $year_ins = $year;
            $value_ins = $value;
            $stmt->bind_param('ssdd', $countryCode_ins, $countryName_ins, $year_ins, $value_ins);
            $stmt->execute();
            $this->stats['imported']++;
        }
    }

    /**
     * Requête HTTP
     */
    private function makeRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? json_decode($response, true) : null;
    }

    /**
     * Statistiques
     */
    private function showStats() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║            STATISTIQUES IMPORT            ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        echo "✅ Nouveaux taux : " . $this->stats['imported'] . "\n";
        echo "🔄 Mises à jour : " . $this->stats['updated'] . "\n";
        echo "❌ Erreurs : " . $this->stats['errors'] . "\n\n";
    }
}

// Exécution
$database = new Database();
$conn = $database->connect();
$importer = new WorldBankImporter($conn);

$importer->importAll();

$conn->close();

echo "✅ Import World Bank terminé !\n\n";
?>