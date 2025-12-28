<?php
/**
 * Système d'internationalisation (i18n)
 */

class I18n {
    private static $lang = 'fr';
    private static $translations = [];
    private static $available_langs = ['fr', 'en'];

    /**
     * Initialiser la langue
     */
    public static function init() {
        // Démarrer la session si pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Récupérer la langue depuis : GET > SESSION > NAVIGATEUR > DÉFAUT
        if (isset($_GET['lang']) && in_array($_GET['lang'], self::$available_langs)) {
            self::$lang = $_GET['lang'];
            $_SESSION['lang'] = self::$lang;
        } elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], self::$available_langs)) {
            self::$lang = $_SESSION['lang'];
        } else {
            // Détecter la langue du navigateur
            $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2);
            self::$lang = in_array($browser_lang, self::$available_langs) ? $browser_lang : 'fr';
            $_SESSION['lang'] = self::$lang;
        }

        // Charger les traductions
        self::loadTranslations();
    }

    /**
     * Charger les fichiers de traduction
     */
    private static function loadTranslations() {
        $file = __DIR__ . '/lang/' . self::$lang . '.php';

        if (file_exists($file)) {
            self::$translations = require $file;
        }
    }

    /**
     * Obtenir la langue actuelle
     */
    public static function getLang() {
        return self::$lang;
    }

    /**
     * Obtenir une traduction
     */
    public static function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$translations;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default ?? $key;
            }
        }

        return $value;
    }

    /**
     * Traduire avec remplacement de variables
     */
    public static function trans($key, $replacements = [], $default = null) {
        $translation = self::get($key, $default);

        foreach ($replacements as $placeholder => $value) {
            $translation = str_replace(':' . $placeholder, $value, $translation);
        }

        return $translation;
    }

    /**
     * Obtenir toutes les langues disponibles
     */
    public static function getAvailableLangs() {
        return self::$available_langs;
    }

    /**
     * Obtenir l'URL avec changement de langue
     */
    public static function getLangUrl($lang) {
        $current_url = $_SERVER['REQUEST_URI'];
        $parsed = parse_url($current_url);

        $query = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }

        $query['lang'] = $lang;

        return $parsed['path'] . '?' . http_build_query($query);
    }
}

// Fonction helper globale
function __($key, $default = null) {
    return I18n::get($key, $default);
}

function trans($key, $replacements = [], $default = null) {
    return I18n::trans($key, $replacements, $default);
}

// Initialiser
I18n::init();