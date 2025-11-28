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
                  SET id_cours = :id_cours,
                      date_seance = :date_seance,
                      heure_debut = :heure_debut,
                      heure_fin = :heure_fin,
                      salle = :salle,
                      type_seance = :type_seance,
                      statut = 'planifie'";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id_cours", $seance->id_cours);
        $stmt->bindParam(":date_seance", $seance->date_seance);
        $stmt->bindParam(":heure_debut", $seance->heure_debut);
        $stmt->bindParam(":heure_fin", $seance->heure_fin);
        $stmt->bindParam(":salle", $seance->salle);
        $stmt->bindParam(":type_seance", $seance->type_seance);
        
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
     * Récupérer toutes les séances d'un cours
     */
    public function findByCours($idCours) {
        $query = "SELECT s.*,
                  COUNT(DISTINCT CASE WHEN p.statut = 'present' THEN p.id_presence END) as nb_presents,
                  COUNT(DISTINCT CASE WHEN p.statut = 'absent' THEN p.id_presence END) as nb_absents
                  FROM " . $this->table . " s
                  LEFT JOIN presences p ON s.id_seance = p.id_seance
                  WHERE s.id_cours = :id_cours
                  GROUP BY s.id_seance
                  ORDER BY s.date_seance DESC, s.heure_debut DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cours", $idCours);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les séances d'un enseignant (via ses cours)
     */
    public function findByEnseignant($idEnseignant, $filters = []) {
        $query = "SELECT s.*, c.nom_cours, c.code_cours,
                  COUNT(DISTINCT i.id_etudiant) as nb_inscrits,
                  COUNT(DISTINCT CASE WHEN p.statut = 'present' THEN p.id_presence END) as nb_presents
                  FROM " . $this->table . " s
                  JOIN cours c ON s.id_cours = c.id_cours
                  LEFT JOIN inscriptions i ON c.id_cours = i.id_cours AND i.statut = 'inscrit'
                  LEFT JOIN presences p ON s.id_seance = p.id_seance
                  WHERE c.id_enseignant = :id_ens";
        
        // Filtres
        if (!empty($filters['statut'])) {
            $query .= " AND s.statut = :statut";
        }
        
        if (!empty($filters['date_debut'])) {
            $query .= " AND s.date_seance >= :date_debut";
        }
        
        if (!empty($filters['date_fin'])) {
            $query .= " AND s.date_seance <= :date_fin";
        }
        
        $query .= " GROUP BY s.id_seance ORDER BY s.date_seance DESC, s.heure_debut DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_ens", $idEnseignant);
        
        if (!empty($filters['statut'])) {
            $stmt->bindParam(':statut', $filters['statut']);
        }
        if (!empty($filters['date_debut'])) {
            $stmt->bindParam(':date_debut', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $stmt->bindParam(':date_fin', $filters['date_fin']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les séances à venir d'un enseignant
     */
    public function findUpcomingByEnseignant($idEnseignant, $limit = 5) {
        $query = "SELECT s.*, c.nom_cours, c.code_cours
                  FROM " . $this->table . " s
                  JOIN cours c ON s.id_cours = c.id_cours
                  WHERE c.id_enseignant = :id_ens
                  AND s.statut IN ('planifie', 'en_cours')
                  AND (s.date_seance > CURDATE() 
                       OR (s.date_seance = CURDATE() AND s.heure_debut >= CURTIME()))
                  ORDER BY s.date_seance ASC, s.heure_debut ASC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_ens", $idEnseignant);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les séances d'un étudiant (via ses inscriptions)
     */
    public function findByEtudiant($idEtudiant, $filters = []) {
        $query = "SELECT s.*, c.nom_cours, c.code_cours,
                  p.statut as presence_statut, 
                  p.date_heure_marquage,
                  p.methode_validation
                  FROM inscriptions i
                  JOIN cours c ON i.id_cours = c.id_cours
                  JOIN " . $this->table . " s ON c.id_cours = s.id_cours
                  LEFT JOIN presences p ON s.id_seance = p.id_seance AND p.id_etudiant = i.id_etudiant
                  WHERE i.id_etudiant = :id_etudiant
                  AND i.statut = 'inscrit'";
        
        if (!empty($filters['statut_seance'])) {
            $query .= " AND s.statut = :statut_seance";
        }
        
        if (!empty($filters['date_debut'])) {
            $query .= " AND s.date_seance >= :date_debut";
        }
        
        $query .= " ORDER BY s.date_seance DESC, s.heure_debut DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        
        if (!empty($filters['statut_seance'])) {
            $stmt->bindParam(':statut_seance', $filters['statut_seance']);
        }
        if (!empty($filters['date_debut'])) {
            $stmt->bindParam(':date_debut', $filters['date_debut']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer les séances actives du jour pour un étudiant
     */
    public function findTodayActiveByEtudiant($idEtudiant) {
        $query = "SELECT s.*, c.nom_cours, c.code_cours,
                  p.statut as presence_statut
                  FROM inscriptions i
                  JOIN cours c ON i.id_cours = c.id_cours
                  JOIN " . $this->table . " s ON c.id_cours = s.id_cours
                  LEFT JOIN presences p ON s.id_seance = p.id_seance AND p.id_etudiant = i.id_etudiant
                  WHERE i.id_etudiant = :id_etudiant
                  AND i.statut = 'inscrit'
                  AND s.date_seance = CURDATE()
                  AND s.statut IN ('planifie', 'en_cours')
                  ORDER BY s.heure_debut ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mettre à jour une séance
     */
    public function update(Seance $seance) {
        $query = "UPDATE " . $this->table . " 
                  SET date_seance = :date_seance,
                      heure_debut = :heure_debut,
                      heure_fin = :heure_fin,
                      salle = :salle,
                      type_seance = :type_seance
                  WHERE id_seance = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":date_seance", $seance->date_seance);
        $stmt->bindParam(":heure_debut", $seance->heure_debut);
        $stmt->bindParam(":heure_fin", $seance->heure_fin);
        $stmt->bindParam(":salle", $seance->salle);
        $stmt->bindParam(":type_seance", $seance->type_seance);
        $stmt->bindParam(":id", $seance->id_seance);
        
        return $stmt->execute();
    }
    
    /**
     * Mettre à jour le statut d'une séance
     */
    public function updateStatut($id, $statut) {
        $query = "UPDATE " . $this->table . " 
                  SET statut = :statut 
                  WHERE id_seance = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":statut", $statut);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Annuler une séance
     */
    public function annuler($id) {
        return $this->updateStatut($id, 'annule');
    }
    
    /**
     * Supprimer une séance (et ses présences en cascade)
     */
    public function delete($id) {
        try {
            $this->conn->beginTransaction();
            
            // Supprimer les présences liées
            $queryPresences = "DELETE FROM presences WHERE id_seance = :id";
            $stmtPresences = $this->conn->prepare($queryPresences);
            $stmtPresences->bindParam(":id", $id);
            $stmtPresences->execute();
            
            // Supprimer la séance
            $query = "DELETE FROM " . $this->table . " WHERE id_seance = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $result = $stmt->execute();
            
            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    /**
     * Compter les séances d'un enseignant
     */
    public function countByEnseignant($idEnseignant, $statut = null) {
        $query = "SELECT COUNT(*) as total
                  FROM " . $this->table . " s
                  JOIN cours c ON s.id_cours = c.id_cours
                  WHERE c.id_enseignant = :id_ens";
        
        if ($statut) {
            $query .= " AND s.statut = :statut";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_ens", $idEnseignant);
        
        if ($statut) {
            $stmt->bindParam(":statut", $statut);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    
    /**
     * Statistiques de présence pour un enseignant
     */
    public function getStatsPresenceByEnseignant($idEnseignant) {
        $query = "SELECT 
                  COUNT(DISTINCT s.id_seance) as total_seances,
                  COUNT(DISTINCT CASE WHEN p.statut = 'present' THEN p.id_presence END) as total_presents,
                  COUNT(DISTINCT CASE WHEN p.statut = 'absent' THEN p.id_presence END) as total_absents,
                  ROUND(
                      (COUNT(DISTINCT CASE WHEN p.statut = 'present' THEN p.id_presence END) * 100.0) / 
                      NULLIF(COUNT(DISTINCT p.id_presence), 0), 
                      2
                  ) as taux_presence
                  FROM cours c
                  JOIN " . $this->table . " s ON c.id_cours = s.id_cours
                  LEFT JOIN presences p ON s.id_seance = p.id_seance
                  WHERE c.id_enseignant = :id_ens
                  AND s.statut = 'termine'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_ens", $idEnseignant);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // ========== MÉTHODES ADMIN ==========
    
    /**
     * Récupérer toutes les séances (pour admin)
     */
    public function findAllAdmin($filters = []) {
        $query = "SELECT s.*, c.nom_cours, c.code_cours,
                  CONCAT(u.prenom, ' ', u.nom) as nom_enseignant,
                  COUNT(DISTINCT i.id_etudiant) as nb_inscrits,
                  COUNT(DISTINCT CASE WHEN p.statut = 'present' THEN p.id_presence END) as nb_presents,
                  COUNT(DISTINCT CASE WHEN p.statut = 'absent' THEN p.id_presence END) as nb_absents
                  FROM " . $this->table . " s
                  JOIN cours c ON s.id_cours = c.id_cours
                  JOIN enseignants ens ON c.id_enseignant = ens.id_enseignant
                  JOIN utilisateurs u ON ens.id_utilisateur = u.id_utilisateur
                  LEFT JOIN inscriptions i ON c.id_cours = i.id_cours AND i.statut = 'inscrit'
                  LEFT JOIN presences p ON s.id_seance = p.id_seance
                  WHERE 1=1";
        
        if (!empty($filters['statut'])) {
            $query .= " AND s.statut = :statut";
        }
        if (!empty($filters['date_debut'])) {
            $query .= " AND s.date_seance >= :date_debut";
        }
        if (!empty($filters['date_fin'])) {
            $query .= " AND s.date_seance <= :date_fin";
        }
        if (!empty($filters['id_enseignant'])) {
            $query .= " AND c.id_enseignant = :id_enseignant";
        }
        if (!empty($filters['id_cours'])) {
            $query .= " AND s.id_cours = :id_cours";
        }
        
        $query .= " GROUP BY s.id_seance ORDER BY s.date_seance DESC, s.heure_debut DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($filters['statut'])) {
            $stmt->bindParam(':statut', $filters['statut']);
        }
        if (!empty($filters['date_debut'])) {
            $stmt->bindParam(':date_debut', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $stmt->bindParam(':date_fin', $filters['date_fin']);
        }
        if (!empty($filters['id_enseignant'])) {
            $stmt->bindParam(':id_enseignant', $filters['id_enseignant']);
        }
        if (!empty($filters['id_cours'])) {
            $stmt->bindParam(':id_cours', $filters['id_cours']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Statistiques globales des séances (admin)
     */
    public function getStatsGlobales() {
        $query = "SELECT 
                  COUNT(*) as total_seances,
                  SUM(CASE WHEN statut = 'planifie' THEN 1 ELSE 0 END) as seances_planifiees,
                  SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as seances_en_cours,
                  SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as seances_terminees,
                  SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) as seances_annulees,
                  COUNT(DISTINCT id_cours) as cours_avec_seances
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Séances par période (pour graphiques admin)
     */
    public function getSeancesByPeriod($dateDebut, $dateFin) {
        $query = "SELECT 
                  DATE(date_seance) as date,
                  COUNT(*) as nb_seances,
                  SUM(CASE WHEN statut = 'termine' THEN 1 ELSE 0 END) as nb_terminees
                  FROM " . $this->table . "
                  WHERE date_seance BETWEEN :date_debut AND :date_fin
                  GROUP BY DATE(date_seance)
                  ORDER BY date_seance";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date_debut', $dateDebut);
        $stmt->bindParam(':date_fin', $dateFin);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>