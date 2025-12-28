<?php
/**
 * Script d'exploration des APIs officielles
 * Sources : data.gov.ma, Bank Al-Maghrib, HCP, World Bank
 */

class APIExplorer {

    private $results = [];

    /**
     * Explorer data.gov.ma (CKAN API)
     */
    public function exploreDataGovMa() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║       EXPLORATION DATA.GOV.MA             ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        // Endpoints CKAN
        $base_url = 'https://www.data.gov.ma/data/api/3/action';

        // 1. Rechercher datasets HCP
        echo "🔍 Recherche datasets HCP...\n";
        $search_keywords = [
            'IPC inflation prix consommation',
            'HCP statistiques',
            'indice prix',
            'inflation maroc'
        ];

        foreach ($search_keywords as $keyword) {
            echo "\n→ Recherche : \"$keyword\"\n";
            $url = $base_url . '/package_search?q=' . urlencode($keyword) . '&rows=5&fq=organization:hcp';
            $response = $this->makeRequest($url);

            if ($response && isset($response['result']['results'])) {
                foreach ($response['result']['results'] as $dataset) {
                    echo "  📦 " . $dataset['title'] . "\n";
                    echo "     ID: " . $dataset['id'] . "\n";
                    echo "     URL: https://www.data.gov.ma/data/dataset/" . $dataset['name'] . "\n";

                    // Vérifier les ressources (fichiers)
                    if (!empty($dataset['resources'])) {
                        echo "     Ressources :\n";
                        foreach ($dataset['resources'] as $resource) {
                            echo "       - " . $resource['name'] . " (" . ($resource['format'] ?? 'N/A') . ")\n";
                            echo "         URL: " . ($resource['url'] ?? 'N/A') . "\n";
                        }
                    }
                    echo "\n";
                }
            }
        }

        // 2. Lister toutes les organisations
        echo "\n🏢 Organisations disponibles :\n";
        $url = $base_url . '/organization_list';
        $response = $this->makeRequest($url);

        if ($response && isset($response['result'])) {
            foreach (array_slice($response['result'], 0, 10) as $org) {
                echo "  - $org\n";
            }
        }
    }

    /**
     * Explorer Bank Al-Maghrib
     */
    public function exploreBankAlMaghrib() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║       EXPLORATION BANK AL-MAGHRIB         ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        // Bank Al-Maghrib n'a pas d'API publique REST
        // Les données sont disponibles via :
        echo "⚠️  Bank Al-Maghrib n'expose pas d'API REST publique\n\n";
        echo "📄 Sources disponibles :\n";
        echo "  1. Site web : https://www.bkam.ma\n";
        echo "  2. Publications : https://www.bkam.ma/Publications-et-recherche\n";
        echo "  3. Données statistiques : https://www.bkam.ma/Statistiques\n";
        echo "  4. RSS Feeds (si disponibles)\n\n";

        echo "💡 Solutions :\n";
        echo "  - Web scraping des pages officielles\n";
        echo "  - Téléchargement manuel des rapports PDF\n";
        echo "  - Import manuel dans la table actualites_economiques\n\n";

        // Tester les pages principales
        echo "🔍 Test des URLs principales :\n";
        $urls = [
            'https://www.bkam.ma',
            'https://www.bkam.ma/Statistiques/Principaux-indicateurs',
        ];

        foreach ($urls as $url) {
            $status = $this->checkUrl($url);
            echo "  " . ($status === 200 ? '✅' : '❌') . " $url (HTTP $status)\n";
        }
    }

    /**
     * Explorer HCP direct
     */
    public function exploreHCP() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║            EXPLORATION HCP                ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        echo "⚠️  HCP n'expose pas d'API REST publique directe\n\n";
        echo "📄 Sources disponibles :\n";
        echo "  1. Site web : https://www.hcp.ma\n";
        echo "  2. IPC : https://www.hcp.ma/Indices-des-prix-a-la-consommation-IPC_r348.html\n";
        echo "  3. Téléchargements : https://www.hcp.ma/downloads/\n";
        echo "  4. Via data.gov.ma (voir ci-dessus)\n\n";

        // Tester URLs
        echo "🔍 Test des URLs principales :\n";
        $urls = [
            'https://www.hcp.ma',
            'https://www.hcp.ma/Indices-des-prix-a-la-consommation-IPC_r348.html',
            'https://www.hcp.ma/downloads/IPC-Indice-des-prix-a-la-consommation_t12173.html',
        ];

        foreach ($urls as $url) {
            $status = $this->checkUrl($url);
            echo "  " . ($status === 200 ? '✅' : '❌') . " $url (HTTP $status)\n";
        }
    }

    /**
     * Explorer World Bank API
     */
    public function exploreWorldBank() {
        echo "\n╔═══════════════════════════════════════════╗\n";
        echo "║          EXPLORATION WORLD BANK           ║\n";
        echo "╚═══════════════════════════════════════════╝\n\n";

        // World Bank API v2
        $base_url = 'https://api.worldbank.org/v2';

        echo "✅ World Bank dispose d'une API REST publique\n";
        echo "📚 Documentation : https://datahelpdesk.worldbank.org/knowledgebase/articles/889392\n\n";

        // 1. Indicateur inflation (FP.CPI.TOTL.ZG)
        echo "🔍 Récupération données inflation (Indicateur: FP.CPI.TOTL.ZG)\n\n";

        $countries = [
            'MA' => 'Maroc',
            'FR' => 'France',
            'ES' => 'Espagne',
            'DZ' => 'Algérie',
            'TN' => 'Tunisie',
            'DE' => 'Allemagne',
            'IT' => 'Italie',
            'PT' => 'Portugal'
        ];

        foreach ($countries as $code => $name) {
            echo "→ $name ($code) :\n";

            // URL : https://api.worldbank.org/v2/country/MA/indicator/FP.CPI.TOTL.ZG?format=json&date=2020:2024
            $url = "$base_url/country/$code/indicator/FP.CPI.TOTL.ZG?format=json&date=2020:2024&per_page=100";

            $response = $this->makeRequest($url);

            if ($response && isset($response[1])) {
                $data = $response[1];

                if (!empty($data)) {
                    echo "  ✅ Données disponibles : " . count($data) . " années\n";

                    // Afficher les 3 dernières années
                    foreach (array_slice($data, 0, 3) as $entry) {
                        $year = $entry['date'] ?? 'N/A';
                        $value = $entry['value'] ?? 'N/A';
                        echo "    - $year : " . ($value !== null ? number_format($value, 2) . '%' : 'N/A') . "\n";
                    }
                } else {
                    echo "  ⚠️  Aucune donnée disponible\n";
                }
            } else {
                echo "  ❌ Erreur API\n";
            }
            echo "\n";
        }

        // 2. Autres indicateurs intéressants
        echo "\n📊 Autres indicateurs disponibles :\n";
        $indicators = [
            'FP.CPI.TOTL.ZG' => 'Inflation (CPI)',
            'NY.GDP.MKTP.KD.ZG' => 'Croissance PIB',
            'SL.UEM.TOTL.ZS' => 'Taux de chômage',
            'SI.POV.NAHC' => 'Taux de pauvreté',
            'SP.POP.TOTL' => 'Population totale'
        ];

        foreach ($indicators as $code => $name) {
            echo "  - $code : $name\n";
        }
    }

    /**
     * Faire une requête HTTP
     */
    private function makeRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Dev uniquement

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && $response) {
            return json_decode($response, true);
        }

        return null;
    }

    /**
     * Vérifier URL
     */
    private function checkUrl($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code;
    }
}

// Exécution
echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   EXPLORATION APIs OFFICIELLES - DONNÉES RÉELLES   ║\n";
echo "╚════════════════════════════════════════════════════╝\n";

$explorer = new APIExplorer();

$explorer->exploreDataGovMa();
$explorer->exploreBankAlMaghrib();
$explorer->exploreHCP();
$explorer->exploreWorldBank();

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║                   CONCLUSION                       ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

echo "✅ APIs disponibles :\n";
echo "  1. data.gov.ma (CKAN) - Datasets HCP\n";
echo "  2. World Bank API - Comparaisons internationales\n\n";

echo "⚠️  Pas d'API REST publique :\n";
echo "  1. Bank Al-Maghrib - Web scraping nécessaire\n";
echo "  2. HCP direct - Utiliser data.gov.ma ou scraping\n\n";

echo "💡 Prochaines étapes :\n";
echo "  1. Implémenter import World Bank pour comparaisons\n";
echo "  2. Parser les datasets data.gov.ma pour IPC Maroc\n";
echo "  3. Scraper Bank Al-Maghrib et HCP pour actualités\n";
echo "  4. Remplacer toutes les données mockées\n\n";

?>