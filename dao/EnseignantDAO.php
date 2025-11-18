<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Enseignant.php';

/**
 * EnseignantDAO - Gestion des opérations BD pour Enseignant
 */
class EnseignantDAO {
    private $conn;
    private $table = "enseignants";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Créer un profil enseignant
     */
    public function create(Enseignant $enseignant) {
        $query = "INSERT INTO " . $this->table . " 
                  SET id_utilisateur = :id_user, 
                      departement = :dept, 
                      grade = :grade";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id_user", $enseignant->id_utilisateur);
        $stmt->bindParam(":dept", $enseignant->departement);
        $stmt->bindParam(":grade", $enseignant->grade);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    /**
     * Récupérer un enseignant par ID utilisateur
     */
    public function findByUserId($userId) {
        $query = "SELECT e.*, u.* 
                  FROM " . $this->table . " e
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  WHERE e.id_utilisateur = :user_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return new Enseignant($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }
    
    /**
     * Récupérer un enseignant par ID
     */
    public function findById($id) {
        $query = "SELECT e.*, u.* 
                  FROM " . $this->table . " e
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  WHERE e.id_enseignant = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return new Enseignant($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }
    
    /**
     * Récupérer les cours d'un enseignant avec stats
     */
    public function getMesCours($idEnseignant) {
        $query = "SELECT c.*, 
                         COUNT(DISTINCT i.id_inscription) as nb_etudiants,
                         COUNT(DISTINCT s.id_seance) as nb_seances
                  FROM cours c
                  LEFT JOIN inscriptions i ON c.id_cours = i.id_cours AND i.statut = 'inscrit'
                  LEFT JOIN seances s ON c.id_cours = s.id_cours
                  WHERE c.id_enseignant = :id_enseignant 
                    AND c.statut = 'actif'
                  GROUP BY c.id_cours
                  ORDER BY c.date_creation DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_enseignant", $idEnseignant);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mettre à jour le profil enseignant
     */
    public function update(Enseignant $enseignant) {
        $query = "UPDATE " . $this->table . " 
                  SET departement = :dept,
                      grade = :grade
                  WHERE id_enseignant = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":dept", $enseignant->departement);
        $stmt->bindParam(":grade", $enseignant->grade);
        $stmt->bindParam(":id", $enseignant->id_enseignant);
        
        return $stmt->execute();
    }
}
?>