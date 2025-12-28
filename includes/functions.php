<?php
/**
 * Fonctions utilitaires pour Maroc Inflation
 */

require_once __DIR__ . '/cache.php';

class InflationCalculator {
    private $db;
    private $cache;

    public function __construct($database) {
        $this->db = $database;
        $this->cache = new Cache();
    }

    /**
     * Récupérer l'IPC pour une date donnée
     */
    public function getIPC($annee, $mois) {
        $sql = "SELECT valeur_ipc FROM ipc_mensuel WHERE annee = ? AND mois = ?";
        $stmt = $this->db->prepare($sql);
        $annee_var = $annee;
        $mois_var = $mois;
        $stmt->bind_param('ii', $annee_var, $mois_var);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return floatval($row['valeur_ipc']);
        }

        return null;
    }

    /**
     * Calculer le pouvoir d'achat entre deux dates
     */
    public function calculerPouvoirAchat($montant, $annee_depart, $mois_depart, $annee_arrivee, $mois_arrivee) {
        $ipc_depart = $this->getIPC($annee_depart, $mois_depart);
        $ipc_arrivee = $this->getIPC($annee_arrivee, $mois_arrivee);

        if (!$ipc_depart || !$ipc_arrivee) {
            return [
                'error' => 'Données non disponibles pour cette période'
            ];
        }

        // Vérifier que la date de départ est antérieure
        $date_depart = strtotime("$annee_depart-$mois_depart-01");
        $date_arrivee = strtotime("$annee_arrivee-$mois_arrivee-01");

        if ($date_depart >= $date_arrivee) {
            return [
                'error' => 'La date de départ doit être antérieure à la date d\'arrivée'
            ];
        }

        $montant_equivalent = $montant * ($ipc_arrivee / $ipc_depart);
        $inflation_cumulee = (($ipc_arrivee - $ipc_depart) / $ipc_depart) * 100;
        $perte_pouvoir_achat = $montant_equivalent - $montant;

        return [
            'montant_initial' => $montant,
            'montant_equivalent' => round($montant_equivalent, 2),
            'inflation_cumulee' => round($inflation_cumulee, 2),
            'perte_pouvoir_achat' => round($perte_pouvoir_achat, 2),
            'ipc_depart' => $ipc_depart,
            'ipc_arrivee' => $ipc_arrivee,
            'periode_depart' => sprintf('%02d/%d', $mois_depart, $annee_depart),
            'periode_arrivee' => sprintf('%02d/%d', $mois_arrivee, $annee_arrivee)
        ];
    }

    /**
     * Récupérer l'inflation actuelle (dernier mois disponible)
     */
    public function getInflationActuelle() {
        return $this->cache->remember('inflation_actuelle', function() {
            $sql = "SELECT * FROM ipc_mensuel
                    ORDER BY annee DESC, mois DESC
                    LIMIT 1";

            $result = $this->db->query($sql);
            return $result->fetch_assoc();
        }, 86400);
    }

    /**
     * Récupérer l'historique complet
     */
    public function getHistorique($annee_debut = null, $annee_fin = null) {
        if (!$annee_debut) $annee_debut = START_YEAR;
        if (!$annee_fin) $annee_fin = CURRENT_YEAR;

        $cache_key = "historique_{$annee_debut}_{$annee_fin}";

        return $this->cache->remember($cache_key, function() use ($annee_debut, $annee_fin) {
            $sql = "SELECT annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle, inflation_sous_jacente
                    FROM ipc_mensuel
                    WHERE annee BETWEEN ? AND ?
                    ORDER BY annee ASC, mois ASC";

            $stmt = $this->db->prepare($sql);
            $annee_debut_stats = $annee_debut;
            $annee_fin_stats = $annee_fin;
            $stmt->bind_param('ii', $annee_debut_stats, $annee_fin_stats);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return $data;
        }, 86400);
    }

    /**
     * Récupérer les derniers N mois d'historique
     */
    public function getHistoriqueLimit($limit = 12) {
        $cache_key = "historique_limit_{$limit}";

        return $this->cache->remember($cache_key, function() use ($limit) {
            $sql = "SELECT annee, mois, valeur_ipc, inflation_mensuelle, inflation_annuelle, inflation_sous_jacente
                    FROM ipc_mensuel
                    ORDER BY annee DESC, mois DESC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $limit_var = $limit;
            $stmt->bind_param('i', $limit_var);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            // Remettre dans l'ordre chronologique
            return array_reverse($data);
        }, 86400);
    }

    /**
     * Récupérer l'inflation par catégorie pour un mois donné
     */
    public function getInflationParCategorie($annee, $mois) {
        $cache_key = "categories_{$annee}_{$mois}";

        return $this->cache->remember($cache_key, function() use ($annee, $mois) {
            $sql = "SELECT categorie, inflation_value, ponderation
                    FROM inflation_categories
                    WHERE annee = ? AND mois = ?
                    ORDER BY ponderation DESC";

            $stmt = $this->db->prepare($sql);
            $annee_ville = $annee;
            $mois_ville = $mois;
            $stmt->bind_param('ii', $annee_ville, $mois_ville);
            $stmt->execute();
            $result = $stmt->get_result();

            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }

            return $categories;
        }, 86400);
    }

    /**
     * Récupérer l'inflation par ville
     */
    public function getInflationParVille($annee, $mois) {
        $sql = "SELECT ville, inflation_value
                FROM ipc_villes
                WHERE annee = ? AND mois = ?
                ORDER BY inflation_value DESC";

        $stmt = $this->db->prepare($sql);
        $annee_cat = $annee;
        $mois_cat = $mois;
        $stmt->bind_param('ii', $annee_cat, $mois_cat);
        $stmt->execute();
        $result = $stmt->get_result();

        $villes = [];
        while ($row = $result->fetch_assoc()) {
            $villes[] = $row;
        }

        return $villes;
    }

    /**
     * Obtenir les statistiques générales
     */
    public function getStatistiques($annee_debut = null, $annee_fin = null) {
        if (!$annee_debut) $annee_debut = START_YEAR;
        if (!$annee_fin) $annee_fin = CURRENT_YEAR;

        $sql = "SELECT
                    AVG(inflation_annuelle) as moyenne,
                    MAX(inflation_annuelle) as max,
                    MIN(inflation_annuelle) as min,
                    COUNT(*) as nb_mois
                FROM ipc_mensuel
                WHERE annee BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $annee_debut_var = $annee_debut;
        $annee_fin_var = $annee_fin;
        $stmt->bind_param('ii', $annee_debut_var, $annee_fin_var);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }
}

/**
 * Fonctions d'affichage et de formatage
 */

function formatMontant($montant) {
    return number_format($montant, 2, ',', ' ') . ' DH';
}

function formatPourcentage($pourcentage) {
    $signe = $pourcentage >= 0 ? '+' : '';
    return $signe . number_format($pourcentage, 2, ',', ' ') . '%';
}

function getMoisNom($mois) {
    $mois_noms = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];
    return $mois_noms[intval($mois)] ?? '';
}

function getMoisCourt($mois) {
    $mois_courts = [
        1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'
    ];
    return $mois_courts[intval($mois)] ?? '';
}

function getCategorieName($categorie) {
    $noms = [
        'alimentation' => 'Alimentation et boissons',
        'energie' => 'Énergie',
        'services' => 'Services',
        'produits_manufactures' => 'Produits manufacturés',
        'tabac_alcool' => 'Tabac et alcool',
        'logement' => 'Logement et charges',
        'transport' => 'Transport',
        'sante' => 'Santé',
        'education' => 'Enseignement',
        'loisirs' => 'Loisirs et culture'
    ];
    return $noms[$categorie] ?? ucfirst(str_replace('_', ' ', $categorie));
}

/**
 * Fonction de sécurité : échapper les données pour l'affichage HTML
 */
function escapeHTML($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Fonction de validation
 */
function validerAnnee($annee) {
    $annee = intval($annee);
    return ($annee >= START_YEAR && $annee <= CURRENT_YEAR);
}

function validerMois($mois) {
    $mois = intval($mois);
    return ($mois >= 1 && $mois <= 12);
}

function validerMontant($montant) {
    $montant = floatval($montant);
    return ($montant > 0 && $montant < 1000000000); // Max 1 milliard
}

/**
 * Logger les erreurs
 */
function logError($message, $context = []) {
    $log_file = __DIR__ . '/../logs/errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? json_encode($context) : '';
    $log_entry = "[$timestamp] $message $context_str\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Générer un token CSRF pour les formulaires
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtenir le dernier taux de change disponible
 * @param string $devise Code devise (EUR, USD, etc.)
 * @param string $type Type de taux (BBE ou VIREMENT)
 * @return array|null ['cours_mad', 'date_taux', 'jours_ecart', 'is_recent']
 */
function getDernierTauxChange($devise = 'EUR', $type = 'VIREMENT') {
    $database = new Database();
    $conn = $database->connect();

    $devise_var = $devise;
    $type_var = $type;

    $sql = "SELECT cours_mad, date_taux
            FROM taux_change
            WHERE devise = ? AND type_taux = ?
            ORDER BY date_taux DESC
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $devise_var, $type_var);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();

        // Calculer écart jours
        $jours_ecart = (strtotime(date('Y-m-d')) - strtotime($data['date_taux'])) / 86400;

        // Considérer comme récent si < 7 jours
        $is_recent = $jours_ecart <= 7;

        // Nom du jour en français
        $jours_fr = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $jour_semaine = $jours_fr[date('w', strtotime($data['date_taux']))];

        $conn->close();

        return [
            'cours_mad' => $data['cours_mad'],
            'date_taux' => $data['date_taux'],
            'jours_ecart' => $jours_ecart,
            'is_recent' => $is_recent,
            'jour_semaine' => $jour_semaine
        ];
    }

    $conn->close();
    return null;
}

/**
 * Afficher un taux de change avec badge de statut
 * @param array $taux Données du taux (de getDernierTauxChange)
 * @param string $devise Nom de la devise
 */
function afficherTauxChange($taux, $devise = 'EUR') {
    if (!$taux) {
        return '<span class="text-muted">Non disponible</span>';
    }

    $cours = number_format($taux['cours_mad'], 4);
    $jours = $taux['jours_ecart'];
    $jour = $taux['jour_semaine'];
    $date = date('d/m/Y', strtotime($taux['date_taux']));

    $html = '<strong class="fs-5">' . $cours . ' MAD</strong>';

    if ($jours < 1) {
        $html .= ' <span class="badge bg-success ms-2">Aujourd\'hui</span>';
    } elseif ($jours <= 3) {
        $html .= ' <span class="badge bg-warning ms-2">' . $jour . ' ' . $date . '</span>';
    } else {
        $html .= ' <span class="badge bg-secondary ms-2">' . $jour . ' ' . $date . '</span>';
    }

    return $html;
}
?>