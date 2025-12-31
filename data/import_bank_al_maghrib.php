<?php
/**
 * Import taux de change Bank Al-Maghrib
 * API officielle avec authentification
 * Basé sur : https://github.com/imadarchid/bkam-wrapper
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class BankAlMaghribImporter {

    private $db;
    private $api_key;
    private $base_url = 'https://apihelpdesk.centralbankofmorocco.ma/BAM/CoursChange/api/CoursChange';

    private $stats = [
        'imported' => 0,
        'updated' => 0,
        'errors' => 0
    ];

    public function __construct($database) {
        $this->db = $database;

        // Charger la clé API depuis .env
        $this->api_key = $_ENV['BAM_API_KEY'] ?? getenv('BAM_API_KEY');

        if (!$this->api_key) {
            throw new Exception('BAM_API_KEY non configurée dans .env');
        }
    }

    /**
     * Importer les cours du jour
     */
    public function importToday() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║   IMPORT BANK AL-MAGHRIB - TAUX CHANGE   ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        $date = date('Y-m-d');
        echo "📅 Date : $date\n\n";

        if ($this->isNonWorkingDay($date)) {
            echo "⏸️  Marché fermé (week-end ou jour férié)\n";
            return;
        }

        echo "→ Import cours billets (BBE)...\n";
        $this->importCoursBBE($date);

        echo "\n→ Import cours virements...\n";
        $this->importCoursVirement($date);

        $this->showStats();
    }

    /**
     * Importer cours billets de banque
     */
    private function importCoursBBE($date) {
        $url = $this->base_url . '/GetCoursBBE';
        $data = $this->makeRequest($url, ['dateValue' => $date]);

        if (!$data) {
            echo "  ❌ Erreur API BBE\n";
            $this->stats['errors']++;
            return;
        }

        if (is_array($data) && !empty($data)) {
            foreach ($data as $rate) {
                $devise = $rate['LibDevise'] ?? $rate['libDevise'] ?? null;
                $achat = $rate['CoursAchat'] ?? $rate['coursAchat'] ?? null;
                $vente = $rate['CoursVente'] ?? $rate['coursVente'] ?? null;

                if ($devise && ($achat || $vente)) {
                    $this->saveRate($date, $devise, 'BBE', $achat, $vente);
                    echo "  ✅ $devise : Achat=$achat, Vente=$vente MAD\n";
                }
            }
        }
    }

    /**
     * Importer cours virements
     */
    private function importCoursVirement($date) {
        $url = $this->base_url . '/GetCoursVirement';
        $data = $this->makeRequest($url, ['dateValue' => $date]);

        if (!$data) {
            echo "  ❌ Erreur API Virement\n";
            $this->stats['errors']++;
            return;
        }

        if (is_array($data) && !empty($data)) {
            foreach ($data as $rate) {
                $devise = $rate['LibDevise'] ?? $rate['libDevise'] ?? null;
                $cours = $rate['Cours'] ?? $rate['cours'] ?? null;

                if ($devise && $cours) {
                    $this->saveRate($date, $devise, 'VIREMENT', null, $cours);
                    echo "  ✅ $devise : $cours MAD\n";
                }
            }
        }
    }

    /**
     * Sauvegarder taux
     */
    private function saveRate($date, $devise, $type, $achat = null, $vente = null) {
        $sql_check = "SELECT id FROM taux_change WHERE date_taux = ? AND devise = ? AND type_taux = ?";
        $stmt = $this->db->prepare($sql_check);
        $stmt->bind_param('sss', $date, $devise, $type);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;

        $cours = $vente ?? $achat ?? 0;

        if ($exists) {
            $sql = "UPDATE taux_change SET cours_mad = ?, cours_achat = ?, cours_vente = ?
                    WHERE date_taux = ? AND devise = ? AND type_taux = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('dddsss', $cours, $achat, $vente, $date, $devise, $type);
            $stmt->execute();
            $this->stats['updated']++;
        } else {
            $sql = "INSERT INTO taux_change (date_taux, devise, type_taux, cours_mad, cours_achat, cours_vente, source)
                    VALUES (?, ?, ?, ?, ?, ?, 'Bank Al-Maghrib API')";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssddd', $date, $devise, $type, $cours, $achat, $vente);
            $stmt->execute();
            $this->stats['imported']++;
        }
    }

    /**
     * Requête API avec authentification
     */
    private function makeRequest($url, $params = []) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Ocp-Apim-Subscription-Key: ' . $this->api_key,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            return json_decode($response, true);
        }

        echo "  ⚠️  HTTP $http_code\n";
        return null;
    }

    /**
     * Vérifier jour non ouvré
     */
    private function isNonWorkingDay($date) {
        $dayOfWeek = date('N', strtotime($date));
        return $dayOfWeek >= 6;
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
$importer = new BankAlMaghribImporter($conn);
$importer->importToday();
$conn->close();
echo "✅ Import terminé !\n\n";
?>