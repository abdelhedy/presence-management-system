<?php
require_once __DIR__ . '/../dao/UserDAO.php';
require_once __DIR__ . '/../dao/CoursDAO.php';

/**
 * AdminController - Gestion des actions administrateur
 */
class AdminController {
    private $userDAO;
    private $coursDAO;
    
    public function __construct() {
        $this->userDAO = new UserDAO();
        $this->coursDAO = new CoursDAO();
    }
    
    /**
     * Récupérer les données du dashboard
     */
    public function getDashboardData() {
        return [
            'stats' => $this->userDAO->getStatistics(),
            'users' => $this->userDAO->findAll(['limit' => 10])
        ];
    }
    
    /**
     * Gérer les utilisateurs
     */
    public function gererUtilisateurs($filters = []) {
        return [
            'users' => $this->userDAO->findAll($filters),
            'stats' => $this->userDAO->getStatistics()
        ];
    }
    
    /**
     * Changer le statut d'un utilisateur
     */
    public function changerStatut($idUser, $newStatut) {
        $valid_statuts = ['actif', 'inactif', 'suspendu'];
        
        if (!in_array($newStatut, $valid_statuts)) {
            return [
                'success' => false,
                'error' => 'Statut invalide.'
            ];
        }
        
        // Vérifier que ce n'est pas un admin
        $user = $this->userDAO->findById($idUser);
        if ($user && $user->type_utilisateur === 'administrateur') {
            return [
                'success' => false,
                'error' => 'Impossible de modifier le statut d\'un administrateur.'
            ];
        }
        
        if ($this->userDAO->updateStatus($idUser, $newStatut)) {
            return [
                'success' => true,
                'message' => 'Statut modifié avec succès !'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erreur lors de la modification du statut.'
        ];
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function supprimerUtilisateur($idUser) {
        // Vérifier que ce n'est pas un admin
        $user = $this->userDAO->findById($idUser);
        
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Utilisateur non trouvé.'
            ];
        }
        
        if ($user->type_utilisateur === 'administrateur') {
            return [
                'success' => false,
                'error' => 'Impossible de supprimer un administrateur.'
            ];
        }
        
        if ($this->userDAO->delete($idUser)) {
            return [
                'success' => true,
                'message' => 'Utilisateur supprimé avec succès !'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erreur lors de la suppression.'
        ];
    }
    
    /**
     * Récupérer les statistiques globales
     */
    public function getStatistiques() {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Stats utilisateurs
        $stats_users = $this->userDAO->getStatistics();
        
        // Stats cours
        $query_cours = "SELECT 
            COUNT(*) as total_cours,
            SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as cours_actifs
        FROM cours";
        $stmt_cours = $conn->prepare($query_cours);
        $stmt_cours->execute();
        $stats_cours = $stmt_cours->fetch(PDO::FETCH_ASSOC);
        
        // Stats séances
        $query_seances = "SELECT 
            COUNT(*) as total_seances,
            SUM(CASE WHEN date_seance = CURDATE() THEN 1 ELSE 0 END) as aujourd_hui,
            SUM(CASE WHEN WEEK(date_seance) = WEEK(CURDATE()) THEN 1 ELSE 0 END) as cette_semaine
        FROM seances";
        $stmt_seances = $conn->prepare($query_seances);
        $stmt_seances->execute();
        $stats_seances = $stmt_seances->fetch(PDO::FETCH_ASSOC);
        
        // Stats présences
        $query_presences = "SELECT 
            COUNT(*) as total_presences,
            SUM(CASE WHEN statut = 'present' THEN 1 ELSE 0 END) as presents,
            SUM(CASE WHEN methode_validation = 'image' THEN 1 ELSE 0 END) as par_reconnaissance
        FROM presences
        WHERE MONTH(date_heure_marquage) = MONTH(CURDATE())
        AND YEAR(date_heure_marquage) = YEAR(CURDATE())";
        $stmt_presences = $conn->prepare($query_presences);
        $stmt_presences->execute();
        $stats_presences = $stmt_presences->fetch(PDO::FETCH_ASSOC);
        
        // Cours populaires
        $query_populaires = "SELECT c.nom_cours, c.code_cours, COUNT(i.id_etudiant) as nb_inscrits
        FROM cours c
        LEFT JOIN inscriptions i ON c.id_cours = i.id_cours AND i.statut = 'inscrit'
        WHERE c.statut = 'actif'
        GROUP BY c.id_cours
        ORDER BY nb_inscrits DESC
        LIMIT 5";
        $stmt_populaires = $conn->prepare($query_populaires);
        $stmt_populaires->execute();
        $cours_populaires = $stmt_populaires->fetchAll(PDO::FETCH_ASSOC);
        
        // Dernières inscriptions
        $query_dernieres = "SELECT u.prenom, u.nom, u.type_utilisateur, u.date_inscription
        FROM utilisateurs u
        ORDER BY u.date_inscription DESC
        LIMIT 10";
        $stmt_dernieres = $conn->prepare($query_dernieres);
        $stmt_dernieres->execute();
        $dernieres_inscriptions = $stmt_dernieres->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'users' => $stats_users,
            'cours' => $stats_cours,
            'seances' => $stats_seances,
            'presences' => $stats_presences,
            'cours_populaires' => $cours_populaires,
            'dernieres_inscriptions' => $dernieres_inscriptions
        ];
    }
}
?>