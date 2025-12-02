<?php
require_once __DIR__ . '/../dao/UserDAO.php';
require_once __DIR__ . '/../dao/EtudiantDAO.php';
require_once __DIR__ . '/../dao/EnseignantDAO.php';
require_once __DIR__ . '/../dao/CoursDAO.php';
require_once __DIR__ . '/../dao/SeanceDAO.php';
require_once __DIR__ . '/../dao/PresenceDAO.php';
require_once __DIR__ . '/../dao/ImageReferenceDAO.php';

/**
 * AdminController - Gestion et statistiques pour l'administrateur
 * Utilise la vue MySQL 'vue_presences_detaillees' et les DAOs
 */
class AdminController {
    private $userDAO;
    private $etudiantDAO;
    private $enseignantDAO;
    private $coursDAO;
    private $seanceDAO;
    private $presenceDAO;
    private $imageDAO;
    private $conn;
    
    public function __construct() {
        $this->userDAO = new UserDAO();
        $this->etudiantDAO = new EtudiantDAO();
        $this->enseignantDAO = new EnseignantDAO();
        $this->coursDAO = new CoursDAO();
        $this->seanceDAO = new SeanceDAO();
        $this->presenceDAO = new PresenceDAO();
        $this->imageDAO = new ImageReferenceDAO();
        
        // Connexion pour la vue MySQL
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Dashboard - Statistiques globales
     */
    public function getDashboardStats() {
        // Stats utilisateurs
        $statsUsers = $this->userDAO->getStatistics();
        
        // Stats cours
        $statsCours = $this->coursDAO->getStatsGlobales();
        
        // Stats séances
        $statsSeances = $this->seanceDAO->getStatsGlobales();
        
        // Stats présences
        $statsPresences = $this->presenceDAO->getStatsGlobales();
        
        // Stats images
        $statsImages = $this->imageDAO->getStats();
        
        return [
            'success' => true,
            'stats' => [
                'utilisateurs' => $statsUsers,
                'cours' => $statsCours,
                'seances' => $statsSeances,
                'presences' => $statsPresences,
                'images' => $statsImages
            ]
        ];
    }
    
    /**
     * Récupérer toutes les présences détaillées via la vue MySQL
     */
    public function getPresencesDetaillees($filters = []) {
        $query = "SELECT * FROM vue_presences_detaillees WHERE 1=1";
        
        // Filtres dynamiques
        if (!empty($filters['statut'])) {
            $query .= " AND statut = :statut";
        }
        if (!empty($filters['nom_cours'])) {
            $query .= " AND nom_cours LIKE :nom_cours";
        }
        if (!empty($filters['date_debut'])) {
            $query .= " AND date_seance >= :date_debut";
        }
        if (!empty($filters['date_fin'])) {
            $query .= " AND date_seance <= :date_fin";
        }
        if (!empty($filters['niveau'])) {
            $query .= " AND niveau = :niveau";
        }
        
        $query .= " ORDER BY date_seance DESC, heure_debut DESC LIMIT 1000";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($filters['statut'])) {
            $stmt->bindParam(':statut', $filters['statut']);
        }
        if (!empty($filters['nom_cours'])) {
            $search = "%{$filters['nom_cours']}%";
            $stmt->bindParam(':nom_cours', $search);
        }
        if (!empty($filters['date_debut'])) {
            $stmt->bindParam(':date_debut', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $stmt->bindParam(':date_fin', $filters['date_fin']);
        }
        if (!empty($filters['niveau'])) {
            $stmt->bindParam(':niveau', $filters['niveau']);
        }
        
        $stmt->execute();
        
        return [
            'success' => true,
            'presences' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }
    
    /**
     * Statistiques par cours
     */
    public function getStatsCours() {
        $stats = $this->presenceDAO->getStatsParCours();
        $repartition = $this->coursDAO->getRepartitionCours();
        
        return [
            'success' => true,
            'stats_presence' => $stats,
            'repartition' => $repartition
        ];
    }
    
    /**
     * Statistiques par enseignant
     */
    public function getStatsEnseignants() {
        $stats = $this->presenceDAO->getStatsParEnseignant();
        
        return [
            'success' => true,
            'stats' => $stats
        ];
    }
    
    /**
     * Alertes : Étudiants avec taux d'absence élevé
     */
    public function getAlertesAbsences($seuil = 30) {
        $alertes = $this->presenceDAO->getAlerteAbsences($seuil);
        
        return [
            'success' => true,
            'alertes' => $alertes,
            'seuil' => $seuil
        ];
    }
    
    /**
     * Top étudiants assidus
     */
    public function getTopEtudiants($limit = 10) {
        $top = $this->presenceDAO->getTopEtudiantsAssidus($limit);
        
        return [
            'success' => true,
            'top_etudiants' => $top
        ];
    }
    
    /**
     * Étudiants sans photo de profil
     */
    public function getEtudiantsSansPhoto() {
        $etudiants = $this->imageDAO->getEtudiantsSansPhoto();
        
        return [
            'success' => true,
            'etudiants' => $etudiants,
            'count' => count($etudiants)
        ];
    }
    
    /**
     * Évolution de la présence sur une période
     */
    public function getEvolutionPresence($dateDebut, $dateFin) {
        $evolution = $this->presenceDAO->getEvolutionPresence($dateDebut, $dateFin);
        $seances = $this->seanceDAO->getSeancesByPeriod($dateDebut, $dateFin);
        
        return [
            'success' => true,
            'evolution_presence' => $evolution,
            'evolution_seances' => $seances
        ];
    }
    
    /**
     * Gestion des utilisateurs
     */
    public function getAllUsers($filters = []) {
        $users = $this->userDAO->findAll($filters);
        
        return [
            'success' => true,
            'users' => $users
        ];
    }
    
    /**
     * Récupérer tous les étudiants avec détails
     */
    public function getAllEtudiants() {
        $etudiants = $this->etudiantDAO->findAll();
        
        return [
            'success' => true,
            'etudiants' => $etudiants
        ];
    }
    
    /**
     * Récupérer tous les enseignants avec détails
     */
    public function getAllEnseignants() {
        $enseignants = $this->enseignantDAO->findAll();
        
        return [
            'success' => true,
            'enseignants' => $enseignants
        ];
    }
    
    /**
     * Récupérer tous les cours
     */
    public function getAllCours($filters = []) {
        $cours = $this->coursDAO->findAll($filters);
        
        return [
            'success' => true,
            'cours' => $cours
        ];
    }
    
    /**
     * Récupérer toutes les séances (avec filtres)
     */
    public function getAllSeances($filters = []) {
        $seances = $this->seanceDAO->findAllAdmin($filters);
        
        return [
            'success' => true,
            'seances' => $seances
        ];
    }
    
    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleUserStatus($userId) {
        // Cette fonctionnalité nécessite une colonne 'statut' dans la table utilisateurs
        // Si vous ne l'avez pas, vous pouvez l'ajouter ou utiliser delete/restore
        
        return [
            'success' => false,
            'error' => 'Fonctionnalité non implémentée. Ajoutez une colonne statut à la table utilisateurs.'
        ];
    }
    
    /**
     * Supprimer un utilisateur (admin)
     */
    public function deleteUser($userId) {
        // Vérifier que ce n'est pas le dernier admin
        $user = $this->userDAO->findById($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Utilisateur non trouvé'
            ];
        }
        
        if ($user->getTypeUtilisateur() === 'administrateur') {
            $stats = $this->userDAO->getStatistics();
            if ($stats['administrateurs'] <= 1) {
                return [
                    'success' => false,
                    'error' => 'Impossible de supprimer le dernier administrateur'
                ];
            }
        }
        
        if ($this->userDAO->delete($userId)) {
            return [
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erreur lors de la suppression'
        ];
    }
    
    /**
     * Générer un rapport PDF/Excel (préparation des données)
     */
    public function prepareReport($type, $filters = []) {
        switch ($type) {
            case 'presences':
                return $this->getPresencesDetaillees($filters);
                
            case 'cours':
                return $this->getAllCours($filters);
                
            case 'etudiants':
                return $this->getAllEtudiants();
                
            case 'statistiques':
                return $this->getDashboardStats();
                
            default:
                return [
                    'success' => false,
                    'error' => 'Type de rapport inconnu'
                ];
        }
    }
}
?>