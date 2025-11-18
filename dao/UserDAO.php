<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';

/**
 * UserDAO - Gestion des opérations BD pour User
 */
class UserDAO {
    private $conn;
    private $table = "utilisateurs";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Créer un nouvel utilisateur
     */
    public function create(User $user) {
        $query = "INSERT INTO " . $this->table . " 
                  SET nom = :nom, 
                      prenom = :prenom, 
                      email = :email, 
                      mot_de_passe = :password, 
                      type_utilisateur = :type,
                      numero_telephone = :tel, 
                      statut = 'actif'";
        
        $stmt = $this->conn->prepare($query);
        
        // Hash du mot de passe
        $hashed_password = password_hash($user->mot_de_passe, PASSWORD_BCRYPT);
        
        $stmt->bindParam(":nom", $user->nom);
        $stmt->bindParam(":prenom", $user->prenom);
        $stmt->bindParam(":email", $user->email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":type", $user->type_utilisateur);
        $stmt->bindParam(":tel", $user->numero_telephone);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    /**
     * Connexion - Vérifier email et mot de passe
     */
    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE email = :email AND statut = 'actif' 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier le mot de passe
            if (password_verify($password, $row['mot_de_passe'])) {
                return new User($row);
            }
        }
        return false;
    }
    
    /**
     * Récupérer un utilisateur par ID
     */
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_utilisateur = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return new User($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }
    
    /**
     * Récupérer un utilisateur par email
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return new User($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }
    
    /**
     * Vérifier si un email existe
     */
    public function emailExists($email) {
        $query = "SELECT id_utilisateur FROM " . $this->table . " WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Récupérer tous les utilisateurs (pour admin)
     */
    public function findAll($filters = []) {
        $query = "SELECT u.*, 
                  CASE WHEN u.type_utilisateur = 'etudiant' THEN e.numero_etudiant ELSE NULL END as numero_etudiant,
                  CASE WHEN u.type_utilisateur = 'enseignant' THEN ens.departement ELSE NULL END as departement
                  FROM " . $this->table . " u
                  LEFT JOIN etudiants e ON u.id_utilisateur = e.id_utilisateur
                  LEFT JOIN enseignants ens ON u.id_utilisateur = ens.id_utilisateur
                  WHERE 1=1";
        
        // Filtres dynamiques
        if (!empty($filters['type'])) {
            $query .= " AND u.type_utilisateur = :type";
        }
        if (!empty($filters['statut'])) {
            $query .= " AND u.statut = :statut";
        }
        if (!empty($filters['search'])) {
            $query .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search)";
        }
        
        $query .= " ORDER BY u.date_inscription DESC";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind des filtres
        if (!empty($filters['type'])) {
            $stmt->bindParam(':type', $filters['type']);
        }
        if (!empty($filters['statut'])) {
            $stmt->bindParam(':statut', $filters['statut']);
        }
        if (!empty($filters['search'])) {
            $search_param = "%{$filters['search']}%";
            $stmt->bindParam(':search', $search_param);
        }
        
        $stmt->execute();
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = new User($row);
        }
        
        return $users;
    }
    
    /**
     * Mettre à jour le statut
     */
    public function updateStatus($id, $newStatus) {
        $query = "UPDATE " . $this->table . " SET statut = :statut WHERE id_utilisateur = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":statut", $newStatus);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id_utilisateur = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Statistiques utilisateurs
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN type_utilisateur = 'etudiant' THEN 1 ELSE 0 END) as etudiants,
                    SUM(CASE WHEN type_utilisateur = 'enseignant' THEN 1 ELSE 0 END) as enseignants,
                    SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs,
                    SUM(CASE WHEN DATE(date_inscription) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as nouveaux_30j
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>