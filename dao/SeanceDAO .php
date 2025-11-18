<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Seance.php';

/**
 * SeanceDAO - Gestion des opérations BD pour Seance
 */
class SeanceDAO {
    private $conn;
    private $table = "seances";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Créer une séance
     */
    public function create(Seance $seance) {
        $query = "INSERT INTO " . $this->table . " 
                  SET id_cours = :cours, 
                      date_seance = :date,
                      heure_debut = :debut, 
                      heure_fin = :fin,
                      salle = :salle, 
                      type_seance = :type, 
                      statut = 'planifie'";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":cours", $seance->id_cours);
        $stmt->bindParam(":date", $seance->date_seance);
        $stmt->bindParam(":debut", $seance->heure_debut);
        $stmt->bindParam(":fin", $seance->heure_fin);
        $stmt->bindParam(":salle", $seance->salle);
        $stmt->bindParam(":type", $seance->type_seance);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    /**
     * Récupérer une séance par ID
     */
    public function findById($id) {
        $query = "SELECT s.*, c.nom_cours, c.code_cours
                  FROM " . $this->table . " s
                  JOIN cours c ON s.id_cours = c.id_cours
                  WHERE s.id_seance = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return new Seance($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }
    
    /**
     * Récupérer les séances d'un cours
     */
    public function findByCours($idCours, $filters = []) {
        $query = "SELECT s.*, c.nom_cours, c.code_cours
                  FROM " . $this->table . " s
                  JOIN cours c ON s.id_cours = c.id_cours
                  WHERE s.id_cours = :id_cours";
        
        if (!empty($filters['date'])) {
            $query .= " AND s.date_seance = :date";
        }
        
        if (!empty($filters['statut'])) {
            $query .= " AND s.statut = :statut";
        }
        
        $query .= " ORDER BY s.date_seance DESC, s.heure_debut DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_cours', $idCours);
        
        if (!empty($filters['date'])) {
            $stmt->bindParam(':date', $filters['date']);
        }
        
        if (!empty($filters['statut'])) {
            $stmt->bindParam(':statut', $filters['statut']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les séances d'aujourd'hui pour un étudiant
     */
    public function getSeancesToday($idEtudiant) {
        $query = "SELECT s.*, c.nom_cours, c.code_cours,
                         p.id_presence,
                         p.statut as presence_statut,
                         p.date_heure_marquage
                  FROM " . $this->table . " s
                  JOIN cours c ON s.id_cours = c.id_cours
                  JOIN inscriptions i ON c.id_cours = i.id_cours
                  LEFT JOIN presences p ON s.id_seance = p.id_seance AND p.id_etudiant = :id_etudiant
                  WHERE i.id_etudiant = :id_etudiant
                    AND s.date_seance = CURDATE()
                    AND s.statut IN ('planifie', 'en_cours')
                    AND i.statut = 'inscrit'
                  ORDER BY s.heure_debut";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_etudiant', $idEtudiant);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mettre à jour une séance
     */
    public function update(Seance $seance) {
        $query = "UPDATE " . $this->table . " 
                  SET date_seance = :date,
                      heure_debut = :debut,
                      heure_fin = :fin,
                      salle = :salle,
                      type_seance = :type,
                      statut = :statut
                  WHERE id_seance = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":date", $seance->date_seance);
        $stmt->bindParam(":debut", $seance->heure_debut);
        $stmt->bindParam(":fin", $seance->heure_fin);
        $stmt->bindParam(":salle", $seance->salle);
        $stmt->bindParam(":type", $seance->type_seance);
        $stmt->bindParam(":statut", $seance->statut);
        $stmt->bindParam(":id", $seance->id_seance);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer une séance
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id_seance = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
}
?>