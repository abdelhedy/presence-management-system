<?php
require_once __DIR__ . '/env.php';

/**
 * Classe Database - Gestion de la connexion PDO
 */
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $this->host = env('DB_HOST', 'localhost');
        $this->db_name = env('DB_NAME', 'systeme_presence');
        $this->username = env('DB_USER', 'root');
        $this->password = env('DB_PASSWORD', '');
    }

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
            error_log("Erreur de connexion à la base de données : " . $exception->getMessage());

            if (env('APP_DEBUG', 'false') === 'true') {
                die("Erreur de connexion : " . $exception->getMessage());
            }
            http_response_code(500);
            die("Service momentanément indisponible. Veuillez réessayer plus tard.");
        }

        return $this->conn;
    }
}
?>