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
class AdminController
{
    private $userDAO;
    private $etudiantDAO;
    private $enseignantDAO;
    private $coursDAO;
    private $seanceDAO;
    private $presenceDAO;
    private $imageDAO;
    private $conn;

    public function __construct()
    {
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
    public function getDashboardStats()
    {
        try {
            // Stats utilisateurs par type
            $stmt = $this->conn->query("
                SELECT type_utilisateur, COUNT(*) as count 
                FROM utilisateurs 
                GROUP BY type_utilisateur
            ");
            $utilisateurs = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $utilisateurs[$row['type_utilisateur']] = $row['count'];
            }

            // Stats cours
            $stmt = $this->conn->query("SELECT COUNT(*) as total FROM cours");
            $totalCours = $stmt->fetch()['total'];

            // Stats séances par statut
            $stmt = $this->conn->query("
                SELECT statut, COUNT(*) as count 
                FROM seances 
                GROUP BY statut
            ");
            $seances = ['total' => 0];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $seances[$row['statut']] = $row['count'];
                $seances['total'] += $row['count'];
            }

            // Stats présences avec méthodes de validation
            $stmt = $this->conn->query("
                SELECT 
                    COUNT(CASE WHEN statut = 'present' THEN 1 END) as presents,
                    COUNT(CASE WHEN statut = 'absent' THEN 1 END) as absents,
                    COUNT(CASE WHEN statut = 'justifie' THEN 1 END) as justifies,
                    COUNT(CASE WHEN methode_validation = 'image' THEN 1 END) as par_image,
                    COUNT(CASE WHEN methode_validation = 'manuel' THEN 1 END) as par_manuel,
                    COUNT(CASE WHEN methode_validation = 'automatique' THEN 1 END) as par_auto,
                    COUNT(*) as total
                FROM presences
            ");
            $presencesData = $stmt->fetch(PDO::FETCH_ASSOC);
            $presences = [
                'presents' => (int)$presencesData['presents'],
                'absents' => (int)$presencesData['absents'],
                'justifies' => (int)$presencesData['justifies'],
                'par_image' => (int)$presencesData['par_image'],
                'par_manuel' => (int)$presencesData['par_manuel'],
                'par_auto' => (int)$presencesData['par_auto'],
                'total' => (int)$presencesData['total'],
                'taux_global' => $presencesData['total'] > 0 ?
                    round(($presencesData['presents'] / $presencesData['total']) * 100, 1) : 0
            ];

            // Stats par cours (top 5)
            $stmt = $this->conn->query("
                SELECT 
                    c.nom_cours,
                    COUNT(p.id_presence) as total_presences,
                    COUNT(CASE WHEN p.statut = 'present' THEN 1 END) as presents,
                    COUNT(CASE WHEN p.statut = 'absent' THEN 1 END) as absents
                FROM cours c
                LEFT JOIN seances s ON c.id_cours = s.id_cours
                LEFT JOIN presences p ON s.id_seance = p.id_seance
                GROUP BY c.id_cours, c.nom_cours
                HAVING total_presences > 0
                ORDER BY total_presences DESC
                LIMIT 6
            ");
            $topCours = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Stats par méthode de validation
            $stmt = $this->conn->query("
                SELECT 
                    COALESCE(methode_validation, 'Non défini') as methode,
                    COUNT(*) as total
                FROM presences
                WHERE statut = 'present'
                GROUP BY methode_validation
            ");
            $parMethode = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Évolution des présences sur les 7 derniers jours
            $stmt = $this->conn->query("
                SELECT 
                    DATE(s.date_seance) as date,
                    COUNT(CASE WHEN p.statut = 'present' THEN 1 END) as presents,
                    COUNT(CASE WHEN p.statut = 'absent' THEN 1 END) as absents
                FROM seances s
                LEFT JOIN presences p ON s.id_seance = p.id_seance
                WHERE s.date_seance >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(s.date_seance)
                ORDER BY date ASC
            ");
            $evolution = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Stats taux de présence par cours
            $stmt = $this->conn->query("
                SELECT 
                    c.nom_cours,
                    COUNT(CASE WHEN p.statut = 'present' THEN 1 END) as presents,
                    COUNT(p.id_presence) as total,
                    ROUND((COUNT(CASE WHEN p.statut = 'present' THEN 1 END) / COUNT(p.id_presence)) * 100, 1) as taux
                FROM cours c
                LEFT JOIN seances s ON c.id_cours = s.id_cours
                LEFT JOIN presences p ON s.id_seance = p.id_seance
                GROUP BY c.id_cours, c.nom_cours
                HAVING total > 0
                ORDER BY taux DESC
                LIMIT 5
            ");
            $tauxParCours = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'stats' => [
                    'utilisateurs' => $utilisateurs,
                    'cours' => ['total' => $totalCours],
                    'seances' => $seances,
                    'presences' => $presences,
                    'top_cours' => $topCours,
                    'par_methode' => $parMethode,
                    'evolution' => $evolution,
                    'taux_par_cours' => $tauxParCours
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer toutes les présences détaillées
     */
    public function getPresencesDetaillees($filters = [])
    {
        try {
            $query = "SELECT 
                p.id_presence,
                p.statut,
                p.methode_validation,
                p.date_marquage,
                s.date_seance,
                s.heure_debut,
                s.heure_fin,
                c.nom_cours,
                c.code_cours,
                CONCAT(u_etud.nom, ' ', u_etud.prenom) as nom_etudiant,
                e.numero_etudiant,
                e.niveau,
                CONCAT(u_ens.nom, ' ', u_ens.prenom) as nom_enseignant
            FROM presences p
            JOIN seances s ON p.id_seance = s.id_seance
            JOIN cours c ON s.id_cours = c.id_cours
            JOIN etudiants e ON p.id_etudiant = e.id_etudiant
            JOIN utilisateurs u_etud ON e.id_utilisateur = u_etud.id_utilisateur
            JOIN enseignants ens ON c.id_enseignant = ens.id_enseignant
            JOIN utilisateurs u_ens ON ens.id_utilisateur = u_ens.id_utilisateur
            WHERE 1=1";

            // Filtres dynamiques
            if (!empty($filters['statut'])) {
                $query .= " AND p.statut = :statut";
            }
            if (!empty($filters['nom_cours'])) {
                $query .= " AND c.nom_cours LIKE :nom_cours";
            }
            if (!empty($filters['date_debut'])) {
                $query .= " AND s.date_seance >= :date_debut";
            }
            if (!empty($filters['date_fin'])) {
                $query .= " AND s.date_seance <= :date_fin";
            }
            if (!empty($filters['niveau'])) {
                $query .= " AND e.niveau = :niveau";
            }

            $query .= " ORDER BY s.date_seance DESC, s.heure_debut DESC LIMIT 1000";

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
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'presences' => []
            ];
        }
    }

    /**
     * Statistiques par cours
     */
    public function getStatsCours()
    {
        $stats = $this->presenceDAO->getStatsParCours();
        // $repartition = $this->coursDAO->getRepartitionCours(); // Méthode non implémentée

        return [
            'success' => true,
            'stats_presence' => $stats,
            'repartition' => []
        ];
    }

    /**
     * Statistiques par enseignant
     */
    public function getStatsEnseignants()
    {
        $stats = $this->presenceDAO->getStatsParEnseignant();

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    /**
     * Alertes : Étudiants avec taux d'absence élevé
     */
    public function getAlertesAbsences($seuil = 30)
    {
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
    public function getTopEtudiants($limit = 10)
    {
        $top = $this->presenceDAO->getTopEtudiantsAssidus($limit);

        return [
            'success' => true,
            'top_etudiants' => $top
        ];
    }

    /**
     * Étudiants sans photo de profil
     */
    public function getEtudiantsSansPhoto()
    {
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
    public function getEvolutionPresence($dateDebut, $dateFin)
    {
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
    public function getAllUsers($filters = [])
    {
        try {
            $query = "SELECT * FROM utilisateurs ORDER BY type_utilisateur, nom";
            $stmt = $this->conn->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'users' => $users
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer tous les étudiants avec détails
     */
    public function getAllEtudiants()
    {
        try {
            $query = "SELECT e.*, u.nom, u.prenom, u.email 
                      FROM etudiants e
                      JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                      ORDER BY u.nom";
            $stmt = $this->conn->query($query);
            $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'etudiants' => $etudiants
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer tous les enseignants avec détails
     */
    public function getAllEnseignants()
    {
        try {
            $query = "SELECT ens.*, u.nom, u.prenom, u.email 
                      FROM enseignants ens
                      JOIN utilisateurs u ON ens.id_utilisateur = u.id_utilisateur
                      ORDER BY u.nom";
            $stmt = $this->conn->query($query);
            $enseignants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'enseignants' => $enseignants
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer tous les cours
     */
    public function getAllCours($filters = [])
    {
        try {
            $query = "SELECT c.*, CONCAT(u.nom, ' ', u.prenom) as nom_enseignant
                      FROM cours c
                      JOIN enseignants e ON c.id_enseignant = e.id_enseignant
                      JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                      ORDER BY c.nom_cours";
            $stmt = $this->conn->query($query);
            $cours = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'cours' => $cours
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer toutes les séances (avec filtres)
     */
    public function getAllSeances($filters = [])
    {
        try {
            $query = "SELECT s.*, c.nom_cours, c.code_cours, CONCAT(u.nom, ' ', u.prenom) as nom_enseignant
                      FROM seances s
                      JOIN cours c ON s.id_cours = c.id_cours
                      JOIN enseignants e ON c.id_enseignant = e.id_enseignant
                      JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                      ORDER BY s.date_seance DESC, s.heure_debut DESC";
            $stmt = $this->conn->query($query);
            $seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'seances' => $seances
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Activer/Désactiver un utilisateur
     */
    public function toggleUserStatus($userId)
    {
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
    public function deleteUser($userId)
    {
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
     * Consulter les présences d'une séance
     */
    public function consulterPresencesSeance($idSeance)
    {
        try {
            // Récupérer toutes les présences pour la séance
            $stmt = $this->conn->prepare("
                SELECT 
                    e.id_etudiant,
                    CONCAT(u.nom, ' ', u.prenom) as nom_complet,
                    u.email,
                    COALESCE(p.statut, 'absent') as statut,
                    p.date_heure_marquage as heure_marquage,
                    p.methode_validation
                FROM inscriptions i
                INNER JOIN etudiants e ON i.id_etudiant = e.id_etudiant
                INNER JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                INNER JOIN seances s ON i.id_cours = s.id_cours
                LEFT JOIN presences p ON p.id_etudiant = e.id_etudiant AND p.id_seance = s.id_seance
                WHERE s.id_seance = ?
                ORDER BY u.nom, u.prenom ASC
            ");

            $stmt->execute([$idSeance]);
            $presences = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculer les statistiques
            $total = count($presences);
            $presents = 0;
            $absents = 0;

            foreach ($presences as $presence) {
                if ($presence['statut'] === 'present') {
                    $presents++;
                } else {
                    $absents++;
                }
            }

            $taux = $total > 0 ? round(($presents / $total) * 100, 1) : 0;

            return [
                'success' => true,
                'presences' => $presences,
                'stats' => [
                    'total' => $total,
                    'presents' => $presents,
                    'absents' => $absents,
                    'taux' => $taux
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération des présences: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Générer un rapport PDF/Excel (préparation des données)
     */
    public function prepareReport($type, $filters = [])
    {
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
