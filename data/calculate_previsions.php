<?php
/**
 * Calcul des prévisions d'inflation
 * Méthodes : Moyenne mobile, Régression linéaire, Tendance
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

class InflationPredictor {
    private $db;
    private $horizon_mois = 6; // Prévoir 6 mois à l'avance

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Calculer les prévisions avec plusieurs méthodes
     */
    public function calculatePredictions() {
        echo "🔮 Calcul des prévisions d'inflation...\n\n";

        // Récupérer les 24 derniers mois (2 ans de données)
        $sql = "SELECT annee, mois, inflation_annuelle
                FROM ipc_mensuel
                ORDER BY annee DESC, mois DESC
                LIMIT 24";

        $result = $this->db->query($sql);
        $historique = [];

        while ($row = $result->fetch_assoc()) {
            $historique[] = $row;
        }

        $historique = array_reverse($historique);

        if (count($historique) < 12) {
            echo "❌ Pas assez de données (minimum 12 mois)\n";
            return false;
        }

        echo "📊 Données historiques : " . count($historique) . " mois\n";

        // Obtenir le dernier mois
        $dernier = end($historique);
        $derniere_annee = $dernier['annee'];
        $dernier_mois = $dernier['mois'];

        echo "📅 Dernier mois : {$dernier_mois}/{$derniere_annee}\n\n";

        // Calculer les prévisions pour les 6 prochains mois
        for ($i = 1; $i <= $this->horizon_mois; $i++) {
            $date_future = $this->addMonths($derniere_annee, $dernier_mois, $i);

            // Méthode 1 : Moyenne mobile (3 derniers mois)
            $prev_mm = $this->moyenneMobile($historique, 3);

            // Méthode 2 : Tendance linéaire
            $prev_tendance = $this->tendanceLineaire($historique, $i);

            // Méthode 3 : Moyenne pondérée (+ de poids aux mois récents)
            $prev_ponderee = $this->moyennePonderee($historique);

            // Combinaison des 3 méthodes (moyenne)
            $prevision = ($prev_mm + $prev_tendance + $prev_ponderee) / 3;

            // Calculer intervalle de confiance (±30% de la prévision)
            $marge = abs($prevision * 0.3);
            $min = $prevision - $marge;
            $max = $prevision + $marge;

            // Insérer dans la base
            $sql = "INSERT INTO previsions_inflation (annee, mois, inflation_prevue, inflation_min, inflation_max, methode)
                    VALUES (?, ?, ?, ?, ?, 'combinee')
                    ON DUPLICATE KEY UPDATE
                        inflation_prevue = VALUES(inflation_prevue),
                        inflation_min = VALUES(inflation_min),
                        inflation_max = VALUES(inflation_max),
                        date_calcul = CURRENT_TIMESTAMP";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iiddd', $date_future['annee'], $date_future['mois'], $prevision, $min, $max);
            $stmt->execute();

            echo sprintf(
                "  %02d/%d : %.2f%% (intervalle: %.2f%% - %.2f%%)\n",
                $date_future['mois'],
                $date_future['annee'],
                $prevision,
                $min,
                $max
            );
        }

        echo "\n✅ Prévisions calculées avec succès\n";
        return true;
    }

    /**
     * Moyenne mobile sur N mois
     */
    private function moyenneMobile($historique, $n = 3) {
        $derniers = array_slice($historique, -$n);
        $somme = array_sum(array_column($derniers, 'inflation_annuelle'));
        return $somme / count($derniers);
    }

    /**
     * Tendance linéaire (régression simple)
     */
    private function tendanceLineaire($historique, $mois_avance = 1) {
        $n = count($historique);

        // Extraire les valeurs
        $x = range(1, $n); // Mois numérotés
        $y = array_column($historique, 'inflation_annuelle');

        // Calcul régression y = ax + b
        $sum_x = array_sum($x);
        $sum_y = array_sum($y);
        $sum_xy = 0;
        $sum_x2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sum_xy += $x[$i] * $y[$i];
            $sum_x2 += $x[$i] * $x[$i];
        }

        $a = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
        $b = ($sum_y - $a * $sum_x) / $n;

        // Prédire pour le mois futur
        $x_futur = $n + $mois_avance;
        $prevision = $a * $x_futur + $b;

        return $prevision;
    }

    /**
     * Moyenne pondérée (poids décroissant)
     */
    private function moyennePonderee($historique, $n = 6) {
        $derniers = array_slice($historique, -$n);
        $somme_ponderee = 0;
        $somme_poids = 0;

        foreach ($derniers as $index => $mois) {
            $poids = $index + 1; // Poids croissant pour les mois récents
            $somme_ponderee += $mois['inflation_annuelle'] * $poids;
            $somme_poids += $poids;
        }

        return $somme_ponderee / $somme_poids;
    }

    /**
     * Ajouter N mois à une date
     */
    private function addMonths($annee, $mois, $n) {
        $mois_total = $mois + $n;
        $annee_future = $annee + floor(($mois_total - 1) / 12);
        $mois_futur = (($mois_total - 1) % 12) + 1;

        return [
            'annee' => $annee_future,
            'mois' => $mois_futur
        ];
    }

    /**
     * Afficher les prévisions
     */
    public function showPredictions() {
        echo "\n📈 PRÉVISIONS ACTUELLES\n";
        echo "========================\n\n";

        $sql = "SELECT * FROM previsions_inflation ORDER BY annee, mois";
        $result = $this->db->query($sql);

        while ($row = $result->fetch_assoc()) {
            echo sprintf(
                "%02d/%d : %.2f%% (intervalle: %.2f%% - %.2f%%) - Calculé le %s\n",
                $row['mois'],
                $row['annee'],
                $row['inflation_prevue'],
                $row['inflation_min'],
                $row['inflation_max'],
                date('d/m/Y', strtotime($row['date_calcul']))
            );
        }

        echo "\n";
    }
}

// Exécution
$database = new Database();
$conn = $database->connect();
$predictor = new InflationPredictor($conn);

echo "\n";
echo "╔════════════════════════════════════════╗\n";
echo "║     CALCUL PRÉVISIONS INFLATION        ║\n";
echo "╚════════════════════════════════════════╝\n";
echo "\n";

$predictor->calculatePredictions();
$predictor->showPredictions();

$conn->close();
?>