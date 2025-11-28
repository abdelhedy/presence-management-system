<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * ImageReferenceDAO - Gestion des images de référence pour reconnaissance faciale
 * 
 * Cette classe gère :
 * - Le stockage des photos de profil des étudiants
 * - Les encodages faciaux (générés par Python)
 * - L'historique des images (un étudiant peut avoir plusieurs photos)
 */
class ImageReferenceDAO {
    private $conn;
    private $table = "images_reference";
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Ajouter une nouvelle image de référence
     * 
     * @param int $idEtudiant
     * @param string $cheminImage - Chemin relatif de l'image
     * @param string $encodageFacial - JSON de l'encodage facial (généré par Python)
     * @return int|false - ID de l'image ou false
     */
    public function create($idEtudiant, $cheminImage, $encodageFacial = null) {
        $query = "INSERT INTO " . $this->table . " 
                  SET id_etudiant = :id_etudiant,
                      chemin_image = :chemin,
                      encodage_facial = :encodage,
                      date_ajout = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->bindParam(":chemin", $cheminImage);
        $stmt->bindParam(":encodage", $encodageFacial);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    /**
     * Récupérer l'image de référence la plus récente d'un étudiant
     * 
     * @param int $idEtudiant
     * @return array|null
     */
    public function findLatestByEtudiant($idEtudiant) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_etudiant = :id_etudiant 
                  ORDER BY date_ajout DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
    
    /**
     * Récupérer toutes les images d'un étudiant (historique)
     * 
     * @param int $idEtudiant
     * @return array
     */
    public function findByEtudiant($idEtudiant) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_etudiant = :id_etudiant 
                  ORDER BY date_ajout DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Vérifier si un étudiant a une image de référence
     * 
     * @param int $idEtudiant
     * @return bool
     */
    public function hasImage($idEtudiant) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE id_etudiant = :id_etudiant";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Récupérer l'encodage facial d'un étudiant
     * Utilisé pour la comparaison lors de la reconnaissance
     * 
     * @param int $idEtudiant
     * @return string|null - JSON de l'encodage
     */
    public function getEncodage($idEtudiant) {
        $image = $this->findLatestByEtudiant($idEtudiant);
        return $image ? $image['encodage_facial'] : null;
    }
    
    /**
     * Récupérer le chemin de l'image d'un étudiant
     * 
     * @param int $idEtudiant
     * @return string|null
     */
    public function getCheminImage($idEtudiant) {
        $image = $this->findLatestByEtudiant($idEtudiant);
        return $image ? $image['chemin_image'] : null;
    }
    
    /**
     * Mettre à jour l'encodage facial d'une image existante
     * Utile si l'encodage est généré après l'upload
     * 
     * @param int $idImage
     * @param string $encodageFacial
     * @return bool
     */
    public function updateEncodage($idImage, $encodageFacial) {
        $query = "UPDATE " . $this->table . " 
                  SET encodage_facial = :encodage 
                  WHERE id_image = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":encodage", $encodageFacial);
        $stmt->bindParam(":id", $idImage);
        
        return $stmt->execute();
    }
    
    /**
     * Supprimer une image
     * 
     * @param int $idImage
     * @return bool
     */
    public function delete($idImage) {
        // Récupérer le chemin pour supprimer le fichier physique
        $query = "SELECT chemin_image FROM " . $this->table . " WHERE id_image = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $idImage);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            $filePath = __DIR__ . '/../' . $image['chemin_image'];
            
            // Supprimer le fichier physique
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Supprimer de la BD
        $deleteQuery = "DELETE FROM " . $this->table . " WHERE id_image = :id";
        $deleteStmt = $this->conn->prepare($deleteQuery);
        $deleteStmt->bindParam(":id", $idImage);
        
        return $deleteStmt->execute();
    }
    
    /**
     * Supprimer toutes les images d'un étudiant sauf la plus récente
     * Utile pour nettoyer l'historique
     * 
     * @param int $idEtudiant
     * @return bool
     */
    public function cleanOldImages($idEtudiant) {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id_etudiant = :id_etudiant 
                  AND id_image NOT IN (
                      SELECT id_image FROM (
                          SELECT id_image FROM " . $this->table . " 
                          WHERE id_etudiant = :id_etudiant 
                          ORDER BY date_ajout DESC 
                          LIMIT 1
                      ) as keep_image
                  )";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        
        return $stmt->execute();
    }
    
    /**
     * Statistiques images - Admin
     * 
     * @return array
     */
    public function getStats() {
        $query = "SELECT 
                  COUNT(DISTINCT id_etudiant) as total_etudiants_avec_photo,
                  COUNT(*) as total_images,
                  (SELECT COUNT(*) FROM etudiants) as total_etudiants,
                  ROUND(
                      (COUNT(DISTINCT id_etudiant) * 100.0) / 
                      NULLIF((SELECT COUNT(*) FROM etudiants), 0), 
                      2
                  ) as pourcentage_couverture
                  FROM " . $this->table;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Liste des étudiants sans photo (pour alertes admin)
     * 
     * @return array
     */
    public function getEtudiantsSansPhoto() {
        $query = "SELECT e.id_etudiant, e.numero_etudiant,
                  u.nom, u.prenom, u.email,
                  e.niveau, e.specialite
                  FROM etudiants e
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  LEFT JOIN " . $this->table . " ir ON e.id_etudiant = ir.id_etudiant
                  WHERE ir.id_image IS NULL
                  ORDER BY u.nom, u.prenom";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>