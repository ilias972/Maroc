<?php
/**
 * Système de cache simple avec fichiers JSON
 */

class Cache {
    private $cache_dir;
    private $default_ttl = 3600; // 1 heure par défaut

    public function __construct($cache_dir = null) {
        $this->cache_dir = $cache_dir ?? __DIR__ . '/../cache';

        // Créer le dossier cache s'il n'existe pas
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }

    /**
     * Récupérer une valeur du cache
     */
    public function get($key) {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);

        // Vérifier l'expiration
        if (isset($data['expires_at']) && time() > $data['expires_at']) {
            $this->delete($key);
            return null;
        }

        return $data['value'] ?? null;
    }

    /**
     * Stocker une valeur dans le cache
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->default_ttl;

        $data = [
            'key' => $key,
            'value' => $value,
            'created_at' => time(),
            'expires_at' => time() + $ttl,
            'ttl' => $ttl
        ];

        $file = $this->getCacheFile($key);

        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Supprimer une valeur du cache
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * Vider tout le cache
     */
    public function clear() {
        $files = glob($this->cache_dir . '/*.json');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Récupérer ou générer (cache-aside pattern)
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Obtenir le chemin du fichier cache
     */
    private function getCacheFile($key) {
        $safe_key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cache_dir . '/' . $safe_key . '.json';
    }

    /**
     * Obtenir les statistiques du cache
     */
    public function getStats() {
        $files = glob($this->cache_dir . '/*.json');
        $total_size = 0;
        $expired = 0;

        foreach ($files as $file) {
            $total_size += filesize($file);

            $data = json_decode(file_get_contents($file), true);
            if (isset($data['expires_at']) && time() > $data['expires_at']) {
                $expired++;
            }
        }

        return [
            'total_files' => count($files),
            'total_size' => $total_size,
            'total_size_human' => $this->formatBytes($total_size),
            'expired_files' => $expired,
            'cache_dir' => $this->cache_dir
        ];
    }

    /**
     * Nettoyer les fichiers expirés
     */
    public function cleanup() {
        $files = glob($this->cache_dir . '/*.json');
        $cleaned = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            if (isset($data['expires_at']) && time() > $data['expires_at']) {
                unlink($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Formater les bytes
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}