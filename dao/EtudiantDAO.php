<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Etudiant.php';

/**
 * EtudiantDAO - Gestion des opérations BD pour Etudiant
 */
class EtudiantDAO {
    private $conn;
    private $table = "etudiants";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Créer un profil étudiant
     */
    public function create(Etudiant $etudiant) {
        $query = "INSERT INTO " . $this->table . " 
                  SET id_utilisateur = :id_user, 
                      numero_etudiant = :numero,
                      niveau = :niveau, 
                      specialite = :specialite, 
                      annee_scolaire = :annee";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id_user", $etudiant->getId());
        $stmt->bindParam(":numero", $etudiant->numero_etudiant);
        $stmt->bindParam(":niveau", $etudiant->niveau);
        $stmt->bindParam(":specialite", $etudiant->specialite);
        $stmt->bindParam(":annee", $etudiant->annee_scolaire);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    /**
     * Récupérer un étudiant par ID utilisateur
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
            return new Etudiant($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }
    
    /**
     * Récupérer un étudiant par ID étudiant
     */
    public function findById($id) {
        $query = "SELECT e.*, u.* 
                  FROM " . $this->table . " e
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  WHERE e.id_etudiant = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return new Etudiant($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }
    
    /**
     * Vérifier si un numéro étudiant existe
     */
    public function numeroExists($numero) {
        $query = "SELECT id_etudiant FROM " . $this->table . " 
                  WHERE numero_etudiant = :numero LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":numero", $numero);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Récupérer les cours d'un étudiant avec stats
     */
    public function getMesCours($idEtudiant) {
        $query = "SELECT c.*, 
                         CONCAT(u.prenom, ' ', u.nom) as nom_enseignant,
                         COUNT(DISTINCT s.id_seance) as total_seances,
                         COUNT(DISTINCT p.id_presence) as total_presences,
                         CASE 
                            WHEN COUNT(DISTINCT s.id_seance) > 0 
                            THEN ROUND((COUNT(DISTINCT CASE WHEN p.statut = 'present' THEN p.id_presence END) / COUNT(DISTINCT s.id_seance)) * 100, 2)
                            ELSE 0 
                         END as taux_presence
                  FROM inscriptions i
                  JOIN cours c ON i.id_cours = c.id_cours
                  JOIN enseignants ens ON c.id_enseignant = ens.id_enseignant
                  JOIN utilisateurs u ON ens.id_utilisateur = u.id_utilisateur
                  LEFT JOIN seances s ON c.id_cours = s.id_cours AND s.date_seance <= CURDATE()
                  LEFT JOIN presences p ON s.id_seance = p.id_seance AND p.id_etudiant = i.id_etudiant
                  WHERE i.id_etudiant = :id_etudiant 
                    AND i.statut = 'inscrit'
                    AND c.statut = 'actif'
                  GROUP BY c.id_cours
                  ORDER BY c.nom_cours";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifier si l'étudiant a une image de profil
     */
    public function hasProfileImage($idEtudiant) {
        $query = "SELECT COUNT(*) as count FROM images_reference WHERE id_etudiant = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idEtudiant);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Mettre à jour le profil étudiant
     */
    public function update(Etudiant $etudiant) {
        $query = "UPDATE " . $this->table . " 
                  SET numero_etudiant = :numero,
                      niveau = :niveau,
                      specialite = :specialite,
                      annee_scolaire = :annee
                  WHERE id_etudiant = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":numero", $etudiant->numero_etudiant);
        $stmt->bindParam(":niveau", $etudiant->niveau);
        $stmt->bindParam(":specialite", $etudiant->specialite);
        $stmt->bindParam(":annee", $etudiant->annee_scolaire);
        $stmt->bindParam(":id", $etudiant->id_etudiant);
        
        return $stmt->execute();
    }
}
?>