<?php
/**
 * Script d'import des données HCP
 *
 * Utilisation : php data/import_hcp.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class HCPImporter {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Import depuis fichier CSV
     * Format CSV attendu : Annee,Mois,IPC,Inflation_Mensuelle,Inflation_Annuelle
     */
    public function importFromCSV($filepath) {
        echo "📥 Import des données depuis $filepath...\n";

        if (!file_exists($filepath)) {
            die("❌ Fichier introuvable : $filepath\n");
        }

        $handle = fopen($filepath, 'r');
        $header = fgetcsv($handle, 1000, ',');

        $count = 0;
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if (count($data) < 5) continue;

            $annee = intval($data[0]);
            $mois = intval($data[1]);
            $ipc = floatval($data[2]);
            $inf_mensuelle = floatval($data[3]);
            $inf_annuelle = floatval($data[4]);

            $sql = "INSERT INTO ipc_mensuel (annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        valeur_ipc = VALUES(valeur_ipc),
                        inflation_mensuelle = VALUES(inflation_mensuelle),
                        inflation_annuelle = VALUES(inflation_annuelle)";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iiddd', $annee, $mois, $ipc, $inf_mensuelle, $inf_annuelle);
            $stmt->execute();

            $count++;
        }

        fclose($handle);
        echo "✅ Import terminé : $count lignes importées\n";
    }

    /**
     * Génération de données de test réalistes pour le développement
     * Simule l'évolution de l'inflation au Maroc de 2007 à 2025
     */
    public function generateTestData() {
        echo "🧪 Génération de données de test réalistes...\n\n";

        $base_ipc = 100;  // Base 2017 = 100
        $count = 0;

        // Événements économiques pour simuler des variations réalistes
        $evenements = [
            '2008' => 0.5,   // Crise financière mondiale
            '2011' => 0.15,  // Printemps arabe - instabilité
            '2020' => -0.3,  // COVID-19 - déflation temporaire
            '2022' => 0.8,   // Guerre Ukraine - forte inflation
            '2023' => 0.3,   // Normalisation
            '2024' => 0.1,   // Stabilité
            '2025' => 0.05   // Inflation basse
        ];

        for ($annee = 2007; $annee <= 2025; $annee++) {
            // Ajustement IPC pour l'année de base
            if ($annee < 2017) {
                $base_ipc = 100 - (2017 - $annee) * 1.5; // Décroissance avant 2017
            } elseif ($annee == 2017) {
                $base_ipc = 100;
            } else {
                // Augmentation progressive après 2017
                $base_ipc = 100 + ($annee - 2017) * 2.5;
            }

            for ($mois = 1; $mois <= 12; $mois++) {
                // Ne pas aller au-delà de décembre 2025
                if ($annee == 2025 && $mois > 12) break;

                // Variations mensuelles réalistes
                $variation_base = isset($evenements[$annee]) ? $evenements[$annee] : 0.1;

                // Saisonnalité (ramadan, rentrée scolaire, etc.)
                $saisonnalite = 0;
                if ($mois == 9) $saisonnalite = 0.3;  // Rentrée scolaire
                if ($mois == 7 || $mois == 8) $saisonnalite = 0.2;  // Vacances d'été

                $variation_mensuelle = $variation_base + $saisonnalite + (rand(-20, 30) / 100);
                $base_ipc += $variation_mensuelle;

                // Calculer l'inflation annuelle (comparaison avec même mois année précédente)
                $sql_prev = "SELECT valeur_ipc FROM ipc_mensuel WHERE annee = ? AND mois = ?";
                $stmt_prev = $this->db->prepare($sql_prev);
                $annee_prev = $annee - 1;
                $stmt_prev->bind_param('ii', $annee_prev, $mois);
                $stmt_prev->execute();
                $result_prev = $stmt_prev->get_result();

                if ($row_prev = $result_prev->fetch_assoc()) {
                    $ipc_prev = $row_prev['valeur_ipc'];
                    $inflation_annuelle = (($base_ipc - $ipc_prev) / $ipc_prev) * 100;
                } else {
                    // Première année, utiliser une valeur par défaut
                    $inflation_annuelle = rand(10, 30) / 10;
                }

                $inflation_mensuelle = $variation_mensuelle / $base_ipc * 100;

                // Inflation sous-jacente (plus stable)
                $inflation_sous_jacente = $inflation_annuelle * 0.7 + rand(-5, 5) / 10;

                $sql = "INSERT INTO ipc_mensuel
                        (annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle, inflation_sous_jacente)
                        VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            valeur_ipc = VALUES(valeur_ipc),
                            inflation_mensuelle = VALUES(inflation_mensuelle),
                            inflation_annuelle = VALUES(inflation_annuelle),
                            inflation_sous_jacente = VALUES(inflation_sous_jacente)";

                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iidddd', $annee, $mois, $base_ipc, $inflation_mensuelle, $inflation_annuelle, $inflation_sous_jacente);
                $stmt->execute();

                $count++;

                // Afficher la progression tous les 12 mois
                if ($mois == 12) {
                    echo "  ✓ Année $annee complétée (IPC: " . number_format($base_ipc, 2) . ", Inflation: " . number_format($inflation_annuelle, 2) . "%)\n";
                }
            }
        }

        echo "\n✅ Données de test générées : $count enregistrements\n\n";

        // Générer aussi les catégories pour les 12 derniers mois
        $this->generateCategoriesData();
    }

    /**
     * Générer des données de catégories pour les derniers mois
     */
    private function generateCategoriesData() {
        echo "📊 Génération des données par catégorie...\n";

        $categories = [
            'alimentation' => ['inflation' => 1.5, 'ponderation' => 23.0],
            'energie' => ['inflation' => -2.0, 'ponderation' => 9.0],
            'services' => ['inflation' => 2.5, 'ponderation' => 35.0],
            'produits_manufactures' => ['inflation' => 0.5, 'ponderation' => 18.0],
            'tabac_alcool' => ['inflation' => 4.0, 'ponderation' => 2.0],
            'logement' => ['inflation' => 1.8, 'ponderation' => 8.0],
            'transport' => ['inflation' => -1.0, 'ponderation' => 3.0],
            'sante' => ['inflation' => 1.2, 'ponderation' => 1.5],
            'education' => ['inflation' => 2.0, 'ponderation' => 0.5],
            'loisirs' => ['inflation' => 1.0, 'ponderation' => 0.0]
        ];

        $annee_actuelle = 2025;
        $count = 0;

        for ($mois = 1; $mois <= 12; $mois++) {
            foreach ($categories as $cat => $data) {
                $variation = rand(-50, 50) / 100;
                $inflation = $data['inflation'] + $variation;

                $sql = "INSERT INTO inflation_categories (annee, mois, categorie, inflation_value, ponderation)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            inflation_value = VALUES(inflation_value),
                            ponderation = VALUES(ponderation)";

                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('iisdd', $annee_actuelle, $mois, $cat, $inflation, $data['ponderation']);
                $stmt->execute();

                $count++;
            }
        }

        echo "✅ $count enregistrements de catégories générés pour 2025\n";
    }

    /**
     * Afficher les statistiques
     */
    public function showStats() {
        echo "\n📈 STATISTIQUES DE LA BASE DE DONNÉES\n";
        echo "=====================================\n\n";

        // Total IPC mensuel
        $result = $this->db->query("SELECT COUNT(*) as count FROM ipc_mensuel");
        $count = $result->fetch_assoc()['count'];
        echo "• Total enregistrements IPC : $count\n";

        // Période couverte
        $result = $this->db->query("SELECT MIN(annee) as min, MAX(annee) as max FROM ipc_mensuel");
        $row = $result->fetch_assoc();
        echo "• Période : {$row['min']} - {$row['max']}\n";

        // Dernier mois
        $result = $this->db->query("SELECT annee, mois, inflation_annuelle FROM ipc_mensuel ORDER BY annee DESC, mois DESC LIMIT 1");
        $row = $result->fetch_assoc();
        echo "• Dernier mois : {$row['mois']}/{$row['annee']} (Inflation: {$row['inflation_annuelle']}%)\n";

        // Catégories
        $result = $this->db->query("SELECT COUNT(*) as count FROM inflation_categories");
        $count = $result->fetch_assoc()['count'];
        echo "• Total catégories : $count\n";

        echo "\n✅ Import terminé avec succès !\n";
    }
}

// ============================================
// EXÉCUTION DU SCRIPT
// ============================================

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║   MAROC INFLATION - IMPORT DONNÉES     ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

$database = new Database();
$conn = $database->connect();
$importer = new HCPImporter($conn);

// Option 1 : Import depuis CSV (si fichier existe)
$csv_file = __DIR__ . '/csv/ipc_maroc_2007_2025.csv';
if (file_exists($csv_file)) {
    echo "📁 Fichier CSV détecté : $csv_file\n";
    $importer->importFromCSV($csv_file);
} else {
    // Option 2 : Générer des données de test
    echo "ℹ️  Aucun fichier CSV trouvé, génération de données de test...\n\n";
    $importer->generateTestData();
}

// Afficher les statistiques
$importer->showStats();

$conn->close();

echo "\n";
?>