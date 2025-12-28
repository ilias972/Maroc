<?php
/**
 * Import IPC HCP via data.gov.ma (CKAN)
 * Version optimisée : 1GB RAM, parsing ligne par ligne
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

ini_set('memory_limit', '1024M');
set_time_limit(600);

class HcpCkanImporter {

    private $db;
    private $ckan_api = 'https://www.data.gov.ma/data/api/3/action';
    private $stats = ['imported' => 0, 'updated' => 0, 'skipped' => 0];

    public function __construct($database) {
        $this->db = $database;
    }

    public function importIPC() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║      IMPORT HCP - IPC DATA.GOV.MA         ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        $url = $this->ckan_api . '/package_search?q=indice+prix+consommation&rows=5';
        $response = $this->makeRequest($url);

        if (!$response || !isset($response['result']['results'])) {
            echo "❌ Erreur CKAN\n";
            return;
        }

        $datasets = $response['result']['results'];
        if (empty($datasets)) {
            echo "⚠️  Aucun dataset\n";
            return;
        }

        $dataset = $datasets[0];
        echo "📦 " . $dataset['title'] . "\n";
        echo "📅 " . $dataset['metadata_modified'] . "\n\n";

        $lastImport = $this->getLastImportDate();
        if ($lastImport && strtotime($dataset['metadata_modified']) <= strtotime($lastImport)) {
            echo "✅ Déjà à jour ($lastImport)\n";
            return;
        }

        $excelResource = null;
        foreach ($dataset['resources'] as $resource) {
            if (isset($resource['format']) && in_array(strtoupper($resource['format']), ['XLSX', 'XLS'])) {
                $excelResource = $resource;
                break;
            }
        }

        if (!$excelResource) {
            echo "❌ Pas de fichier Excel\n";
            return;
        }

        echo "📥 Téléchargement...\n";
        $this->downloadAndParse($excelResource['url']);
        $this->saveImportDate($dataset['metadata_modified']);
        $this->showStats();
    }

    private function downloadAndParse($url) {
        $tempFile = tempnam(sys_get_temp_dir(), 'hcp_');

        $ch = curl_init($url);
        $fp = fopen($tempFile, 'w+');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120
        ]);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        echo "✅ " . round(filesize($tempFile)/1024/1024, 2) . " MB\n";
        echo "📊 Parsing...\n";

        try {
            $reader = IOFactory::createReaderForFile($tempFile);
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(0);

            $spreadsheet = $reader->load($tempFile);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            echo "→ $highestRow lignes\n\n";

            for ($row = 2; $row <= $highestRow; $row++) {
                $annee = $sheet->getCell("A$row")->getValue();
                $mois = $sheet->getCell("B$row")->getValue();
                $ipc = $sheet->getCell("C$row")->getValue();
                $inf_m = $sheet->getCell("D$row")->getValue();
                $inf_a = $sheet->getCell("E$row")->getValue();

                if ($annee && $mois && is_numeric($ipc)) {
                    $this->saveIPC($annee, $mois, $ipc, $inf_m, $inf_a);

                    if ($row % 50 === 0) {
                        echo "  → $row/" . $highestRow . "\n";
                    }
                }

                if ($row % 100 === 0) {
                    gc_collect_cycles();
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

        } catch (Exception $e) {
            echo "❌ " . $e->getMessage() . "\n";
        }

        unlink($tempFile);
    }

    private function saveIPC($annee, $mois, $ipc, $inf_m, $inf_a) {
        $annee = intval($annee);
        $mois = intval($mois);
        $ipc = floatval($ipc);
        $inf_m = $inf_m ? floatval($inf_m) : null;
        $inf_a = $inf_a ? floatval($inf_a) : null;

        if ($annee < 2000 || $annee > 2030 || $mois < 1 || $mois > 12) {
            $this->stats['skipped']++;
            return;
        }

        $sql = "SELECT id FROM ipc_mensuel WHERE annee = ? AND mois = ?";
        $stmt = $this->db->prepare($sql);
        $annee_check = $annee;
        $mois_check = $mois;
        $stmt->bind_param('ii', $annee_check, $mois_check);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;

        if ($exists) {
            $sql = "UPDATE ipc_mensuel SET valeur_ipc = ?, inflation_mensuelle = ?,
                    inflation_annuelle = ?, source = 'HCP data.gov.ma' WHERE annee = ? AND mois = ?";
            $stmt = $this->db->prepare($sql);
            $ipc_upd = $ipc;
            $inf_m_upd = $inf_m;
            $inf_a_upd = $inf_a;
            $annee_upd = $annee;
            $mois_upd = $mois;
            $stmt->bind_param('dddii', $ipc_upd, $inf_m_upd, $inf_a_upd, $annee_upd, $mois_upd);
            $stmt->execute();
            $this->stats['updated']++;
        } else {
            $sql = "INSERT INTO ipc_mensuel (annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle, source)
                    VALUES (?, ?, ?, ?, ?, 'HCP data.gov.ma')";
            $stmt = $this->db->prepare($sql);
            $annee_ins = $annee;
            $mois_ins = $mois;
            $ipc_ins = $ipc;
            $inf_m_ins = $inf_m;
            $inf_a_ins = $inf_a;
            $stmt->bind_param('iiddd', $annee_ins, $mois_ins, $ipc_ins, $inf_m_ins, $inf_a_ins);
            $stmt->execute();
            $this->stats['imported']++;
        }
    }

    private function getLastImportDate() {
        $sql = "SELECT value FROM site_config WHERE config_key = 'last_hcp_import'";
        $result = $this->db->query($sql);
        return $result && $result->num_rows > 0 ? $result->fetch_assoc()['value'] : null;
    }

    private function saveImportDate($date) {
        $sql = "INSERT INTO site_config (config_key, value) VALUES ('last_hcp_import', ?)
                ON DUPLICATE KEY UPDATE value = ?";
        $stmt = $this->db->prepare($sql);
        $date1 = $date;
        $date2 = $date;
        $stmt->bind_param('ss', $date1, $date2);
        $stmt->execute();
    }

    private function makeRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ? json_decode($response, true) : null;
    }

    private function showStats() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║            STATISTIQUES                   ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";
        echo "✅ Nouveau : " . $this->stats['imported'] . "\n";
        echo "🔄 MAJ : " . $this->stats['updated'] . "\n";
        echo "⏭️  Ignoré : " . $this->stats['skipped'] . "\n\n";
    }
}

$database = new Database();
$conn = $database->connect();
$importer = new HcpCkanImporter($conn);
$importer->importIPC();
$conn->close();
echo "✅ Import terminé !\n\n";
?>