<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * InscriptionDAO - Gestion des inscriptions aux cours
 */
class InscriptionDAO {
    private $conn;
    private $table = "inscriptions";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Créer une inscription manuelle
     * 
     * Note: Les inscriptions automatiques sont gérées par le trigger 
     * trg_after_cours_insert lors de la création d'un cours
     */
    public function create($idCours, $idEtudiant) {
        // Vérifier si l'inscription existe déjà
        if ($this->exists($idCours, $idEtudiant)) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table . " 
                  SET id_cours = :id_cours,
                      id_etudiant = :id_etudiant,
                      date_inscription = NOW(),
                      statut = 'inscrit'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cours", $idCours);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    /**
     * Vérifier si une inscription existe
     */
    public function exists($idCours, $idEtudiant) {
        $query = "SELECT id_inscription FROM " . $this->table . " 
                  WHERE id_cours = :id_cours 
                  AND id_etudiant = :id_etudiant 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cours", $idCours);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Récupérer toutes les inscriptions d'un cours
     */
    public function findByCours($idCours) {
        $query = "SELECT i.*, 
                  e.numero_etudiant,
                  u.nom, u.prenom, u.email
                  FROM " . $this->table . " i
                  JOIN etudiants e ON i.id_etudiant = e.id_etudiant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  WHERE i.id_cours = :id_cours
                  AND i.statut = 'inscrit'
                  ORDER BY u.nom, u.prenom";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cours", $idCours);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer toutes les inscriptions d'un étudiant
     */
    public function findByEtudiant($idEtudiant) {
        $query = "SELECT i.*, 
                  c.nom_cours, c.code_cours, c.niveau, c.specialite,
                  CONCAT(u.prenom, ' ', u.nom) as nom_enseignant
                  FROM " . $this->table . " i
                  JOIN cours c ON i.id_cours = c.id_cours
                  JOIN enseignants ens ON c.id_enseignant = ens.id_enseignant
                  JOIN utilisateurs u ON ens.id_utilisateur = u.id_utilisateur
                  WHERE i.id_etudiant = :id_etudiant
                  AND i.statut = 'inscrit'
                  AND c.statut = 'actif'
                  ORDER BY c.nom_cours";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Désinscrire un étudiant d'un cours
     */
    public function desinscrire($idCours, $idEtudiant) {
        $query = "UPDATE " . $this->table . " 
                  SET statut = 'desinscrit' 
                  WHERE id_cours = :id_cours 
                  AND id_etudiant = :id_etudiant";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cours", $idCours);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        
        return $stmt->execute();
    }
    
    /**
     * Réinscrire un étudiant
     */
    public function reinscrire($idCours, $idEtudiant) {
        $query = "UPDATE " . $this->table . " 
                  SET statut = 'inscrit',
                      date_inscription = NOW()
                  WHERE id_cours = :id_cours 
                  AND id_etudiant = :id_etudiant";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cours", $idCours);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer une inscription
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id_inscription = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Compter les inscrits d'un cours
     */
    public function countByCours($idCours) {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE id_cours = :id_cours 
                  AND statut = 'inscrit'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cours", $idCours);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    /**
     * Compter le nombre d'étudiants éligibles pour un cours
     * Avant sa création (pour prévisualisation)
     */
    // public function countEtudiantsEligibles($niveau, $specialite, $anneeScolaire) {
    //     $query = "SELECT COUNT(*) as total
    //               FROM etudiants
    //               WHERE niveau = :niveau
    //               AND specialite = :specialite
    //               AND annee_scolaire = :annee";
        
    //     $stmt = $this->conn->prepare($query);
    //     $stmt->bindParam(':niveau', $niveau);
    //     $stmt->bindParam(':specialite', $specialite);
    //     $stmt->bindParam(':annee', $anneeScolaire);
    //     $stmt->execute();
        
    //     $result = $stmt->fetch(PDO::FETCH_ASSOC);
    //     return $result['total'] ?? 0;
    // }
}
?>