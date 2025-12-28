<?php
/**
 * Fonctions SEO et meta tags
 */

class SEO {

    /**
     * Générer les meta tags pour une page
     */
    public static function generateMetaTags($config = []) {
        $defaults = [
            'title' => 'Inflation au Maroc - Données HCP',
            'description' => 'Suivez l\'inflation au Maroc avec des données officielles du HCP. Calculateur, historique, comparaisons internationales et prévisions.',
            'keywords' => 'inflation maroc, IPC maroc, HCP, prix consommation, économie maroc, inflation actuelle',
            'image' => '/assets/images/og-image.jpg',
            'url' => 'http://localhost:8000',
            'type' => 'website',
            'locale' => 'fr_MA'
        ];

        $meta = array_merge($defaults, $config);

        // Échapper les valeurs
        foreach ($meta as $key => $value) {
            $meta[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        echo "\n<!-- SEO Meta Tags -->\n";
        echo "<meta name=\"description\" content=\"{$meta['description']}\">\n";
        echo "<meta name=\"keywords\" content=\"{$meta['keywords']}\">\n";
        echo "<meta name=\"author\" content=\"Maroc Inflation\">\n";
        echo "<meta name=\"robots\" content=\"index, follow\">\n";
        echo "<link rel=\"canonical\" href=\"{$meta['url']}\">\n";

        echo "\n<!-- Open Graph (Facebook, LinkedIn) -->\n";
        echo "<meta property=\"og:title\" content=\"{$meta['title']}\">\n";
        echo "<meta property=\"og:description\" content=\"{$meta['description']}\">\n";
        echo "<meta property=\"og:image\" content=\"{$meta['image']}\">\n";
        echo "<meta property=\"og:url\" content=\"{$meta['url']}\">\n";
        echo "<meta property=\"og:type\" content=\"{$meta['type']}\">\n";
        echo "<meta property=\"og:locale\" content=\"{$meta['locale']}\">\n";
        echo "<meta property=\"og:site_name\" content=\"Maroc Inflation\">\n";

        echo "\n<!-- Twitter Card -->\n";
        echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        echo "<meta name=\"twitter:title\" content=\"{$meta['title']}\">\n";
        echo "<meta name=\"twitter:description\" content=\"{$meta['description']}\">\n";
        echo "<meta name=\"twitter:image\" content=\"{$meta['image']}\">\n";

        echo "\n<!-- Schema.org (Google) -->\n";
        self::generateSchema($meta);
    }

    /**
     * Générer le Schema.org JSON-LD
     */
    public static function generateSchema($meta) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'Maroc Inflation',
            'url' => $meta['url'],
            'description' => $meta['description'],
            'inLanguage' => 'fr-MA',
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Maroc Inflation',
                'url' => $meta['url']
            ]
        ];

        echo "<script type=\"application/ld+json\">\n";
        echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo "\n</script>\n";
    }

    /**
     * Générer le fil d'Ariane (breadcrumb)
     */
    public static function generateBreadcrumb($items) {
        if (empty($items)) return;

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        foreach ($items as $index => $item) {
            $breadcrumb['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'] ?? null
            ];
        }

        echo "\n<!-- Breadcrumb Schema -->\n";
        echo "<script type=\"application/ld+json\">\n";
        echo json_encode($breadcrumb, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo "\n</script>\n";
    }

    /**
     * Générer Schema pour les données d'inflation
     */
    public static function generateDatasetSchema($data) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => 'Données d\'Inflation au Maroc',
            'description' => 'Indice des Prix à la Consommation (IPC) et taux d\'inflation mensuels au Maroc depuis 2007',
            'url' => 'http://localhost:8000/inflation_historique.php',
            'keywords' => ['inflation', 'IPC', 'Maroc', 'HCP', 'économie'],
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
            'creator' => [
                '@type' => 'Organization',
                'name' => 'Haut-Commissariat au Plan (HCP)',
                'url' => 'https://www.hcp.ma'
            ],
            'temporalCoverage' => '2007/..',
            'spatialCoverage' => [
                '@type' => 'Place',
                'name' => 'Maroc'
            ]
        ];

        if (!empty($data)) {
            $schema['distribution'] = [
                '@type' => 'DataDownload',
                'encodingFormat' => 'application/json',
                'contentUrl' => 'http://localhost:8000/api/get_ipc.php'
            ];
        }

        echo "\n<!-- Dataset Schema -->\n";
        echo "<script type=\"application/ld+json\">\n";
        echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo "\n</script>\n";
    }
}