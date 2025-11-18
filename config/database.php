<?php
/**
 * Classe Database - Gestion de la connexion PDO
 */
class Database {
    private $host = "localhost";
    private $db_name = "systeme_presence";
    private $username = "root";
    private $password = "";
    public $conn;

    /**
     * Obtenir la connexion PDO
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Erreur de connexion: " . $exception->getMessage();
            die();
        }

        return $this->conn;
    }
}
?>