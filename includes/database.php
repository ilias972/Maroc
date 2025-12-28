<?php
/**
 * Classe de connexion à la base de données
 */

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    /**
     * Établir la connexion à MySQL
     */
    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name
            );
            
            // Définir le charset
            $this->conn->set_charset(DB_CHARSET);
            
            // Vérifier les erreurs de connexion
            if ($this->conn->connect_error) {
                throw new Exception("Erreur de connexion : " . $this->conn->connect_error);
            }
            
        } catch(Exception $e) {
            if (APP_DEBUG) {
                die("Erreur de connexion à la base de données : " . $e->getMessage());
            } else {
                error_log("Erreur DB : " . $e->getMessage());
                die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
            }
        }

        return $this->conn;
    }
    
    /**
     * Fermer la connexion
     */
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
?>