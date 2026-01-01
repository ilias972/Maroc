<?php
/**
 * Import taux de change depuis ExchangeRate-API
 * API gratuite et fiable : https://www.exchangerate-api.com/
 * Données réelles en temps réel (pas mockées)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class TauxChangeImporter {

    private $db;
    // API publique gratuite (1500 requêtes/mois)
    private $base_url = 'https://api.exchangerate-api.com/v4/latest/';

    private $stats = [
        'imported' => 0,
        'updated' => 0,
        'errors' => 0
    ];

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Importer les cours du jour
     */
    public function importToday() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║   IMPORT TAUX DE CHANGE - TEMPS RÉEL     ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        $date = date('Y-m-d');
        $dayName = $this->getDayName($date);
        $heure = date('H:i');
        echo "📅 Date : $date ($dayName) à $heure\n";
        echo "🌐 Source : ExchangeRate-API (données réelles)\n\n";

        // Devises à importer (base MAD)
        $devises = ['EUR', 'USD', 'GBP', 'CHF'];

        foreach ($devises as $devise) {
            echo "→ Import $devise...\n";
            $this->importDevise($devise, $date);
        }

        $this->showStats();
    }

    /**
     * Importer une devise
     */
    private function importDevise($devise, $date) {
        // Obtenir le taux depuis l'API (base = devise, target = MAD)
        $url = $this->base_url . $devise;
        $data = $this->makeRequest($url);

        if (!$data || !isset($data['rates']['MAD'])) {
            echo "  ❌ Erreur : impossible d'obtenir le taux $devise\n";
            $this->stats['errors']++;
            return;
        }

        $taux_mad = $data['rates']['MAD'];

        // Sauvegarder dans la base
        $this->saveRate($date, $devise, 'VIREMENT', $taux_mad);

        echo "  ✅ $devise = " . number_format($taux_mad, 4) . " MAD\n";
    }

    /**
     * Sauvegarder taux
     */
    private function saveRate($date, $devise, $type, $cours) {
        $sql_check = "SELECT id FROM taux_change WHERE date_taux = ? AND devise = ? AND type_taux = ?";
        $stmt = $this->db->prepare($sql_check);
        $stmt->bind_param('sss', $date, $devise, $type);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;

        if ($exists) {
            $sql = "UPDATE taux_change SET cours_mad = ?, updated_at = NOW()
                    WHERE date_taux = ? AND devise = ? AND type_taux = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('dsss', $cours, $date, $devise, $type);
            $stmt->execute();
            $this->stats['updated']++;
        } else {
            $sql = "INSERT INTO taux_change (date_taux, devise, type_taux, cours_mad, source, updated_at)
                    VALUES (?, ?, ?, ?, 'ExchangeRate-API', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssd', $date, $devise, $type, $cours);
            $stmt->execute();
            $this->stats['imported']++;
        }
    }

    /**
     * Requête API
     */
    private function makeRequest($url) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            } else {
                echo "  ⚠️  Erreur JSON: " . json_last_error_msg() . "\n";
                return null;
            }
        }

        echo "  ⚠️  HTTP $http_code";
        if ($curl_error) {
            echo " - $curl_error";
        }
        echo "\n";

        return null;
    }

    /**
     * Obtenir le nom du jour
     */
    private function getDayName($date) {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return $days[date('w', strtotime($date))];
    }

    /**
     * Statistiques
     */
    private function showStats() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║          STATISTIQUES IMPORT              ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";
        echo "✅ Nouveaux : " . $this->stats['imported'] . "\n";
        echo "🔄 MAJ : " . $this->stats['updated'] . "\n";
        echo "❌ Erreurs : " . $this->stats['errors'] . "\n\n";
    }
}

$database = new Database();
$conn = $database->connect();
$importer = new TauxChangeImporter($conn);
$importer->importToday();
$conn->close();
echo "✅ Import terminé !\n\n";
?>
