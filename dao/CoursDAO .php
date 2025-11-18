<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Cours.php';

/**
 * CoursDAO - Gestion des opérations BD pour Cours
 */
class CoursDAO {
    private $conn;
    private $table = "cours";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Créer un cours
     */
    public function create(Cours $cours) {
        $query = "INSERT INTO " . $this->table . " 
                  SET nom_cours = :nom, 
                      code_cours = :code, 
                      id_enseignant = :id_ens,
                      description = :desc, 
                      niveau = :niveau, 
                      specialite = :specialite,
                      annee_scolaire = :annee, 
                      statut = 'actif'";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nom", $cours->nom_cours);
        $stmt->bindParam(":code", $cours->code_cours);
        $stmt->bindParam(":id_ens", $cours->id_enseignant);
        $stmt->bindParam(":desc", $cours->description);
        $stmt->bindParam(":niveau", $cours->niveau);
        $stmt->bindParam(":specialite", $cours->specialite);
        $stmt->bindParam(":annee", $cours->annee_scolaire);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    /**
     * Récupérer un cours par ID
     */
    public function findById($id) {
        $query = "SELECT c.*, 
                         CONCAT(u.prenom, ' ', u.nom) as nom_enseignant
                  FROM " . $this->table . " c
                  JOIN enseignants e ON c.id_enseignant = e.id_enseignant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  WHERE c.id_cours = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return new Cours($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }
    
    /**
     * Vérifier si un code cours existe
     */
    public function codeExists($code, $excludeId = null) {
        $query = "SELECT id_cours FROM " . $this->table . " WHERE code_cours = :code";
        
        if ($excludeId) {
            $query .= " AND id_cours != :exclude_id";
        }
        
        $query .= " LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $code);
        
        if ($excludeId) {
            $stmt->bindParam(":exclude_id", $excludeId);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Récupérer tous les cours actifs
     */
    public function findAll($filters = []) {
        $query = "SELECT c.*, 
                         CONCAT(u.prenom, ' ', u.nom) as nom_enseignant,
                         COUNT(DISTINCT i.id_inscription) as nb_etudiants
                  FROM " . $this->table . " c
                  JOIN enseignants e ON c.id_enseignant = e.id_enseignant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  LEFT JOIN inscriptions i ON c.id_cours = i.id_cours AND i.statut = 'inscrit'
                  WHERE c.statut = 'actif'";
        
        if (!empty($filters['niveau'])) {
            $query .= " AND c.niveau = :niveau";
        }
        
        if (!empty($filters['specialite'])) {
            $query .= " AND c.specialite = :specialite";
        }
        
        $query .= " GROUP BY c.id_cours ORDER BY c.nom_cours";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($filters['niveau'])) {
            $stmt->bindParam(':niveau', $filters['niveau']);
        }
        
        if (!empty($filters['specialite'])) {
            $stmt->bindParam(':specialite', $filters['specialite']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mettre à jour un cours
     */
    public function update(Cours $cours) {
        $query = "UPDATE " . $this->table . " 
                  SET nom_cours = :nom,
                      code_cours = :code,
                      description = :desc,
                      niveau = :niveau,
                      specialite = :specialite
                  WHERE id_cours = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nom", $cours->nom_cours);
        $stmt->bindParam(":code", $cours->code_cours);
        $stmt->bindParam(":desc", $cours->description);
        $stmt->bindParam(":niveau", $cours->niveau);
        $stmt->bindParam(":specialite", $cours->specialite);
        $stmt->bindParam(":id", $cours->id_cours);
        
        return $stmt->execute();
    }
    
    /**
     * Archiver un cours (soft delete)
     */
    public function archive($id) {
        $query = "UPDATE " . $this->table . " SET statut = 'archive' WHERE id_cours = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer définitivement un cours
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id_cours = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
}
?>