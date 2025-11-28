<?php
require_once __DIR__ . '/../dao/PresenceDAO.php';
require_once __DIR__ . '/../dao/SeanceDAO.php';
require_once __DIR__ . '/../dao/ImageReferenceDAO.php';
require_once __DIR__ . '/../models/Presence.php';

/**
 * PresenceController - Gestion des présences
 */
class PresenceController {
    private $presenceDAO;
    private $seanceDAO;
    private $imageDAO;
    
    public function __construct() {
        $this->presenceDAO = new PresenceDAO();
        $this->seanceDAO = new SeanceDAO();
        $this->imageDAO = new ImageReferenceDAO();
    }
    
    /**
     * Marquer une présence par reconnaissance faciale
     */
    public function marquerPresenceReconnaissanceFaciale($idSeance, $idEtudiant, $scoreReconnaissance) {
        // Vérifier que la séance existe et est en cours
        $seance = $this->seanceDAO->findById($idSeance);
        
        if (!$seance) {
            return [
                'success' => false,
                'error' => 'Séance non trouvée'
            ];
        }
        
        if ($seance->statut !== 'en_cours' && $seance->statut !== 'planifie') {
            return [
                'success' => false,
                'error' => 'Cette séance n\'est plus active'
            ];
        }
        
        // Vérifier si l'étudiant a une image de référence
        if (!$this->imageDAO->hasImage($idEtudiant)) {
            return [
                'success' => false,
                'error' => 'Aucune photo de référence trouvée. Veuillez ajouter une photo de profil.'
            ];
        }
        
        // Créer la présence
        $presence = new Presence();
        $presence->id_seance = $idSeance;
        $presence->id_etudiant = $idEtudiant;
        $presence->statut = 'present';
        $presence->methode_validation = 'image';
        $presence->score_reconnaissance = $scoreReconnaissance;
        
        // Validation
        $errors = $presence->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }
        
        // Enregistrer la présence
        $presenceId = $this->presenceDAO->create($presence);
        
        if ($presenceId) {
            return [
                'success' => true,
                'message' => 'Présence enregistrée avec succès',
                'presence_id' => $presenceId,
                'score' => $scoreReconnaissance
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erreur lors de l\'enregistrement de la présence'
        ];
    }
    
    /**
     * Marquer manuellement une présence (enseignant)
     */
    public function marquerManuel($idSeance, $idEtudiant, $statut) {
        if (!in_array($statut, ['present', 'absent', 'justifie'])) {
            return [
                'success' => false,
                'error' => 'Statut invalide'
            ];
        }
        
        if ($this->presenceDAO->marquerManuel($idSeance, $idEtudiant, $statut)) {
            return [
                'success' => true,
                'message' => 'Présence mise à jour avec succès'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erreur lors de la mise à jour'
        ];
    }
    
    /**
     * Récupérer la liste de présence d'une séance
     */
    public function getListePresenceSeance($idSeance) {
        $liste = $this->presenceDAO->getListePresenceSeance($idSeance);
        
        return [
            'success' => true,
            'liste' => $liste
        ];
    }
    
    /**
     * Récupérer l'historique de présence d'un étudiant
     */
    public function getHistoriqueEtudiant($idEtudiant, $filters = []) {
        $historique = $this->presenceDAO->findByEtudiant($idEtudiant, $filters);
        
        return [
            'success' => true,
            'historique' => $historique
        ];
    }
    
    /**
     * Obtenir les statistiques de présence d'un étudiant
     */
    public function getStatsEtudiant($idEtudiant, $idCours = null) {
        $stats = $this->presenceDAO->getStatsByEtudiant($idEtudiant, $idCours);
        
        return [
            'success' => true,
            'stats' => $stats
        ];
    }
    
    /**
     * Obtenir les statistiques par cours pour un étudiant
     */
    public function getStatsParCoursEtudiant($idEtudiant) {
        $stats = $this->presenceDAO->getStatsByCours($idEtudiant);
        
        return [
            'success' => true,
            'stats' => $stats
        ];
    }
    
    /**
     * Supprimer une présence (correction)
     */
    public function delete($id) {
        if ($this->presenceDAO->delete($id)) {
            return [
                'success' => true,
                'message' => 'Présence supprimée avec succès'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erreur lors de la suppression'
        ];
    }
}
?>