<?php
/**
 * Système d'authentification pour l'administration
 */

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isAuthenticated() {
        return isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_username']);
    }

    // NOUVEAU : Récupération plus robuste de l'IP (gère les proxys comme Cloudflare)
    private function getRealIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function login($username, $password) {
        $ip_address = $this->getRealIp();

        if ($this->isBlocked($ip_address)) {
            $this->recordAttempt($ip_address, $username, false);
            return ['success' => false, 'error' => 'Trop de tentatives. Réessayez dans 15 minutes.'];
        }

        $sql = "SELECT id, username, password_hash, email, is_active
                FROM admin_users
                WHERE username = ? AND is_active = TRUE";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->recordAttempt($ip_address, $username, false);
            return ['success' => false, 'error' => 'Identifiants incorrects']; // Ne pas indiquer si l'utilisateur existe
        }

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password_hash'])) {
            $this->recordAttempt($ip_address, $username, false);
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }

        $this->recordAttempt($ip_address, $username, true);

        // Générer code 2FA sécurisé
        $code_2fa = $this->generate2FACode($user['id']);

        $_SESSION['pending_2fa_user_id'] = $user['id'];
        $_SESSION['pending_2fa_username'] = $user['username'];

        // CORRECTION : Envoi réel d'email (à configurer avec votre serveur SMTP ou fonction mail)
        $to = $user['email'];
        $subject = "Votre code de connexion 2FA - " . SITE_NAME;
        $message = "Votre code de vérification est : " . $code_2fa . "\nIl expirera dans 10 minutes.";
        $headers = "From: noreply@" . parse_url(SITE_URL, PHP_URL_HOST);
        
        @mail($to, $subject, $message, $headers); // Commenter si le serveur mail n'est pas actif en dev

        return [
            'success' => true,
            'require_2fa' => true,
            // CORRECTION : On ne renvoie plus le code en clair au front-end
            'message' => 'Un code de vérification a été envoyé à votre adresse email.'
        ];
    }

    public function complete2FALogin($code) {
        if (!isset($_SESSION['pending_2fa_user_id'])) {
            return ['success' => false, 'error' => 'Session invalide'];
        }

        $user_id = $_SESSION['pending_2fa_user_id'];

        if ($this->verify2FACode($user_id, $code)) {
            $sql = "SELECT id, username, email FROM admin_users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            // CORRECTION : Prévenir la fixation de session
            session_regenerate_id(true);

            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_login_time'] = time();

            unset($_SESSION['pending_2fa_user_id']);
            unset($_SESSION['pending_2fa_username']);

            $update_sql = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $this->db->prepare($update_sql);
            $update_stmt->bind_param('i', $user['id']);
            $update_stmt->execute();

            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Code 2FA invalide ou expiré'];
    }

    // ... (Le reste des méthodes logout, getCurrentUser, changePassword, requireAuth reste identique) ...

    public function generate2FACode($user_id) {
        // CORRECTION : Utilisation de random_int au lieu de rand
        try {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            // Fallback très rare si le générateur de nombres pseudo-aléatoires dysfonctionne
            $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT); 
        }

        $expires = date('Y-m-d H:i:s', time() + 600);

        $delete_sql = "DELETE FROM two_factor_codes WHERE user_id = ? AND (used = TRUE OR expires_at < NOW())";
        $delete_stmt = $this->db->prepare($delete_sql);
        $delete_stmt->bind_param('i', $user_id);
        $delete_stmt->execute();

        $sql = "INSERT INTO two_factor_codes (user_id, code, expires_at) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $user_id, $code, $expires);
        $stmt->execute();

        return $code;
    }

    // ... (verify2FACode, isBlocked, recordAttempt restent identiques) ...
}
?>
