<?php
/**
 * Import données d'inflation internationales
 * Source : Trading Economics (API gratuite limitée)
 *
 * Note : Trading Economics nécessite une clé API
 * Alternative : Scraping ou données manuelles
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class InternationalInflationImporter {
    private $db;

    // Pays à suivre (code ISO alpha-3)
    private $countries = [
        'MAR' => 'Maroc',
        'FRA' => 'France',
        'ESP' => 'Espagne',
        'DZA' => 'Algérie',
        'TUN' => 'Tunisie',
        'DEU' => 'Allemagne',
        'ITA' => 'Italie',
        'PRT' => 'Portugal'
    ];

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Importer depuis CSV manuel
     */
    public function importFromCSV($filepath) {
        echo "📥 Import données internationales depuis CSV...\n";

        if (!file_exists($filepath)) {
            echo "❌ Fichier introuvable\n";
            return false;
        }

        $handle = fopen($filepath, 'r');
        $header = fgetcsv($handle, 1000, ',');

        $count = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $pays = trim($data[0]);
            $code_pays = trim($data[1]);
            $annee = intval($data[2]);
            $mois = intval($data[3]);
            $inflation = floatval($data[4]);

            $sql = "INSERT INTO inflation_internationale (pays, code_pays, annee, mois, inflation_annuelle)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        inflation_annuelle = VALUES(inflation_annuelle)";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssiid', $pays, $code_pays, $annee, $mois, $inflation);
            $stmt->execute();

            $count++;
        }

        fclose($handle);
        echo "✅ $count enregistrements importés\n";

        return true;
    }

    /**
     * Générer des données exemple pour démo
     */
    public function generateSampleData() {
        echo "🧪 Génération de données exemple (12 derniers mois)...\n";

        $count = 0;

        foreach ($this->countries as $code => $nom) {
            // Inflation de base par pays (approximative)
            $base_inflation = [
                'MAR' => 1.5,
                'FRA' => 1.3,
                'ESP' => 2.8,
                'DZA' => 4.5,
                'TUN' => 6.2,
                'DEU' => 2.2,
                'ITA' => 3.1,
                'PRT' => 2.5
            ];

            $base = $base_inflation[$code] ?? 2.0;

            for ($m = 1; $m <= 12; $m++) {
                $annee = 2024;
                $mois = $m;

                // Variation aléatoire
                $variation = (rand(-20, 20) / 100);
                $inflation = $base + $variation;

                $sql = "INSERT INTO inflation_internationale (pays, code_pays, annee, mois, inflation_annuelle, source)
                        VALUES (?, ?, ?, ?, ?, 'Données exemple')
                        ON DUPLICATE KEY UPDATE
                            inflation_annuelle = VALUES(inflation_annuelle)";

                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ssiid', $nom, $code, $annee, $mois, $inflation);
                $stmt->execute();

                $count++;
            }
        }

        echo "✅ $count enregistrements générés\n";
    }

    /**
     * Afficher les statistiques
     */
    public function showStats() {
        echo "\n📊 STATISTIQUES DONNÉES INTERNATIONALES\n";
        echo "========================================\n\n";

        // Total par pays
        $sql = "SELECT ANY_VALUE(pays) as pays, code_pays, COUNT(*) as count
                FROM inflation_internationale
                GROUP BY code_pays
                ORDER BY ANY_VALUE(pays)";

        $result = $this->db->query($sql);

        while ($row = $result->fetch_assoc()) {
            echo "  {$row['pays']} ({$row['code_pays']}) : {$row['count']} mois\n";
        }

        // Dernières données
        echo "\n📅 DERNIÈRES DONNÉES :\n";

        $sql = "SELECT pays, annee, mois, inflation_annuelle
                FROM inflation_internationale
                ORDER BY annee DESC, mois DESC, pays
                LIMIT 10";

        $result = $this->db->query($sql);

        while ($row = $result->fetch_assoc()) {
            $inflation = number_format($row['inflation_annuelle'], 2);
            echo "  {$row['pays']} - {$row['mois']}/{$row['annee']} : {$inflation}%\n";
        }

        echo "\n";
    }
}

// Exécution
$database = new Database();
$conn = $database->connect();
$importer = new InternationalInflationImporter($conn);

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║   IMPORT DONNÉES INTERNATIONALES       ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

// Générer des données exemple pour démo
$importer->generateSampleData();
$importer->showStats();

$conn->close();

echo "✅ Import terminé !\n\n";
?>