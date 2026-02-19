<?php
/**
 * Système d'authentification pour l'administration
 */

class Auth {
    private $db;

    public function __construct($database) {
        $this->db = $database;

        // Démarrer la session si pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Vérifier si l'utilisateur est authentifié
     */
    public function isAuthenticated() {
        return isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_username']);
    }

    /**
     * NOUVEAU : Récupération plus robuste de l'IP
     */
    private function getRealIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Connexion utilisateur
     */
    public function login($username, $password) {
        $ip_address = $this->getRealIp();

        // Vérifier si l'IP est bloquée
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
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }

        $user = $result->fetch_assoc();

        if (!password_verify($password, $user['password_hash'])) {
            $this->recordAttempt($ip_address, $username, false);
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }

        // Enregistrer tentative réussie
        $this->recordAttempt($ip_address, $username, true);

        // NOUVEAU : Générer code 2FA sécurisé
        $code_2fa = $this->generate2FACode($user['id']);

        // Stocker temporairement l'ID utilisateur (pas encore connecté)
        $_SESSION['pending_2fa_user_id'] = $user['id'];
        $_SESSION['pending_2fa_username'] = $user['username'];

        // Envoi réel d'email (à adapter selon la configuration de votre serveur)
        $to = $user['email'];
        $subject = "Votre code de connexion 2FA - " . (defined('SITE_NAME') ? SITE_NAME : 'Administration');
        $message = "Votre code de vérification est : " . $code_2fa . "\nIl expirera dans 10 minutes.";
        $headers = "From: noreply@" . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
        
        @mail($to, $subject, $message, $headers);

        return [
            'success' => true,
            'require_2fa' => true,
            'message' => 'Un code de vérification a été envoyé à votre adresse email.'
        ];
    }

    /**
     * Finaliser la connexion après 2FA
     */
    public function complete2FALogin($code) {
        if (!isset($_SESSION['pending_2fa_user_id'])) {
            return ['success' => false, 'error' => 'Session invalide'];
        }

        $user_id = $_SESSION['pending_2fa_user_id'];

        if ($this->verify2FACode($user_id, $code)) {
            // Récupérer les infos utilisateur
            $sql = "SELECT id, username, email FROM admin_users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            // CORRECTION : Prévenir la fixation de session
            session_regenerate_id(true);

            // Créer la session
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_login_time'] = time();

            // Nettoyer les variables temporaires
            unset($_SESSION['pending_2fa_user_id']);
            unset($_SESSION['pending_2fa_username']);

            // Mettre à jour last_login
            $update_sql = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
            $update_stmt = $this->db->prepare($update_sql);
            $update_stmt->bind_param('i', $user['id']);
            $update_stmt->execute();

            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Code 2FA invalide ou expiré'];
    }

    /**
     * Déconnexion
     */
    public function logout() {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
        return true;
    }

    /**
     * Obtenir l'utilisateur connecté
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'id' => $_SESSION['admin_user_id'],
            'username' => $_SESSION['admin_username'],
            'email' => $_SESSION['admin_email'] ?? null,
            'login_time' => $_SESSION['admin_login_time'] ?? null
        ];
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword($user_id, $old_password, $new_password) {
        $sql = "SELECT password_hash FROM admin_users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'error' => 'Utilisateur non trouvé'];
        }

        $user = $result->fetch_assoc();

        if (!password_verify($old_password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Ancien mot de passe incorrect'];
        }

        if (strlen($new_password) < 8) {
            return ['success' => false, 'error' => 'Le mot de passe doit faire au moins 8 caractères'];
        }
        if (!preg_match('/[A-Z]/', $new_password)) {
            return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins une majuscule'];
        }
        if (!preg_match('/[0-9]/', $new_password)) {
            return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins un chiffre'];
        }

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $update_sql = "UPDATE admin_users SET password_hash = ? WHERE id = ?";
        $update_stmt = $this->db->prepare($update_sql);
        $update_stmt->bind_param('si', $new_hash, $user_id);
        $update_stmt->execute();

        return ['success' => true, 'message' => 'Mot de passe changé avec succès'];
    }

    /**
     * Rediriger si non authentifié
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('Location: secure-access-xyz2024.php');
            exit;
        }
    }

    /**
     * Générer un code 2FA
     */
    public function generate2FACode($user_id) {
        // CORRECTION : Utilisation de random_int (cryptographiquement sécurisé)
        try {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
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

    /**
     * Vérifier un code 2FA
     */
    public function verify2FACode($user_id, $code) {
        $sql = "SELECT id FROM two_factor_codes
                WHERE user_id = ? AND code = ? AND expires_at > NOW() AND used = FALSE";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $user_id, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $update_sql = "UPDATE two_factor_codes SET used = TRUE WHERE id = ?";
            $update_stmt = $this->db->prepare($update_sql);
            $update_stmt->bind_param('i', $row['id']);
            $update_stmt->execute();

            return true;
        }

        return false;
    }

    /**
     * Vérifier si l'IP est bloquée (trop de tentatives)
     */
    public function isBlocked($ip_address) {
        $sql = "SELECT COUNT(*) as attempts FROM login_attempts
                WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                AND successful = FALSE";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $ip_address);
        $stmt->execute();
        $result = $stmt->get_result();
        $attempts = $result->fetch_assoc()['attempts'];

        return $attempts >= 5;
    }

    /**
     * Enregistrer une tentative de connexion
     */
    public function recordAttempt($ip_address, $username, $successful) {
        $sql = "INSERT INTO login_attempts (ip_address, username, successful) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssi', $ip_address, $username, $successful);
        $stmt->execute();

        $cleanup_sql = "DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $this->db->query($cleanup_sql);
    }
}
?>
