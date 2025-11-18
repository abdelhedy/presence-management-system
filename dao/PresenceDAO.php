<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Presence.php';

/**
 * PresenceDAO - Gestion des opérations BD pour Presence
 */
class PresenceDAO {
    private $conn;
    private $table = "presences";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Marquer une présence
     */
    public function create(Presence $presence) {
        $query = "INSERT INTO " . $this->table . " 
                  SET id_seance = :seance, 
                      id_etudiant = :etudiant,
                      statut = :statut, 
                      methode_validation = :methode,
                      score_reconnaissance = :score";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":seance", $presence->id_seance);
        $stmt->bindParam(":etudiant", $presence->id_etudiant);
        $stmt->bindParam(":statut", $presence->statut);
        $stmt->bindParam(":methode", $presence->methode_validation);
        $stmt->bindParam(":score", $presence->score_reconnaissance);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    /**
     * Vérifier si une présence existe déjà
     */
    public function exists($idSeance, $idEtudiant) {
        $query = "SELECT id_presence FROM " . $this->table . " 
                  WHERE id_seance = :seance AND id_etudiant = :etudiant 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":seance", $idSeance);
        $stmt->bindParam(":etudiant", $idEtudiant);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Récupérer les présences d'une séance
     */
    public function findBySeance($idSeance) {
        $query = "SELECT p.*, 
                         u.nom as nom_etudiant, 
                         u.prenom as prenom_etudiant,
                         e.numero_etudiant,
                         u.email
                  FROM " . $this->table . " p
                  JOIN etudiants e ON p.id_etudiant = e.id_etudiant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  WHERE p.id_seance = :seance
                  ORDER BY u.nom, u.prenom";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seance', $idSeance);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les présences d'un cours avec tous les étudiants inscrits
     */
    public function getPresencesByCours($idCours, $date) {
        $query = "SELECT 
                         e.id_etudiant,
                         e.numero_etudiant,
                         u.nom,
                         u.prenom,
                         u.email,
                         s.id_seance,
                         s.heure_debut,
                         s.heure_fin,
                         s.salle,
                         p.statut,
                         p.date_heure_marquage,
                         p.score_reconnaissance,
                         p.methode_validation
                  FROM inscriptions i
                  JOIN etudiants e ON i.id_etudiant = e.id_etudiant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  LEFT JOIN seances s ON s.id_cours = i.id_cours AND s.date_seance = :date
                  LEFT JOIN presences p ON p.id_seance = s.id_seance AND p.id_etudiant = i.id_etudiant
                  WHERE i.id_cours = :cours
                    AND i.statut = 'inscrit'
                  ORDER BY u.nom, u.prenom";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cours', $idCours);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les statistiques de présence d'un étudiant
     */
    public function getStatsByEtudiant($idEtudiant, $idCours = null) {
        $query = "SELECT 
                     COUNT(DISTINCT s.id_seance) as total_seances,
                     COUNT(DISTINCT CASE WHEN p.statut = 'present' THEN p.id_presence END) as presents,
                     COUNT(DISTINCT CASE WHEN p.statut = 'absent' OR p.id_presence IS NULL THEN s.id_seance END) as absents,
                     COUNT(DISTINCT CASE WHEN p.statut = 'retard' THEN p.id_presence END) as retards
                  FROM inscriptions i
                  JOIN cours c ON i.id_cours = c.id_cours
                  LEFT JOIN seances s ON c.id_cours = s.id_cours AND s.date_seance <= CURDATE()
                  LEFT JOIN presences p ON s.id_seance = p.id_seance AND p.id_etudiant = i.id_etudiant
                  WHERE i.id_etudiant = :etudiant
                    AND i.statut = 'inscrit'";
        
        if ($idCours) {
            $query .= " AND c.id_cours = :cours";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':etudiant', $idEtudiant);
        
        if ($idCours) {
            $stmt->bindParam(':cours', $idCours);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mettre à jour une présence
     */
    public function update(Presence $presence) {
        $query = "UPDATE " . $this->table . " 
                  SET statut = :statut,
                      methode_validation = :methode,
                      score_reconnaissance = :score
                  WHERE id_presence = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":statut", $presence->statut);
        $stmt->bindParam(":methode", $presence->methode_validation);
        $stmt->bindParam(":score", $presence->score_reconnaissance);
        $stmt->bindParam(":id", $presence->id_presence);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer une présence
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id_presence = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
}
?>