<?php
require_once __DIR__ . '/../dao/CoursDAO.php';
require_once __DIR__ . '/../dao/InscriptionDAO.php';
require_once __DIR__ . '/../models/Cours.php';

/**
 * CoursController - Gestion des cours
 */
class CoursController
{
    private $coursDAO;
    private $inscriptionDAO;

    public function __construct()
    {
        $this->coursDAO = new CoursDAO();
        $this->inscriptionDAO = new InscriptionDAO();
    }

    /**
     * Créer un nouveau cours
     * Note: Le trigger MySQL crée automatiquement les inscriptions
     */
    public function create($data)
    {
        // Créer l'objet Cours
        $cours = new Cours();
        $cours->nom_cours = $data['nom_cours'];
        $cours->code_cours = $data['code_cours'];
        $cours->id_enseignant = $data['id_enseignant'];
        $cours->description = $data['description'] ?? '';
        $cours->niveau = $data['niveau'];
        $cours->specialite = $data['specialite'];
        $cours->annee_scolaire = $data['annee_scolaire'];

        // Validation
        $errors = $cours->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Vérifier si le code cours existe déjà
        if ($this->coursDAO->codeExists($cours->code_cours)) {
            return [
                'success' => false,
                'error' => 'Ce code de cours existe déjà'
            ];
        }

        // Créer le cours (le trigger fera les inscriptions automatiquement)
        $coursId = $this->coursDAO->create($cours);

        if ($coursId) {
            // Compter les inscriptions créées par le trigger
            $nbInscriptions = $this->inscriptionDAO->countByCours($coursId);

            return [
                'success' => true,
                'message' => 'Cours créé avec succès',
                'cours_id' => $coursId,
                'inscriptions_count' => $nbInscriptions
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la création du cours'
        ];
    }

    /**
     * Prévisualiser le nombre d'étudiants qui seront inscrits
     * Avant la création du cours
     */
    // public function previewInscriptions($niveau, $specialite, $anneeScolaire) {
    //     $count = $this->inscriptionDAO->countEtudiantsEligibles($niveau, $specialite, $anneeScolaire);

    //     return [
    //         'success' => true,
    //         'count' => $count
    //     ];
    // }

    /**
     * Récupérer un cours par ID
     */
    public function getById($id)
    {
        $cours = $this->coursDAO->findById($id);

        if ($cours) {
            return [
                'success' => true,
                'cours' => $cours
            ];
        }

        return [
            'success' => false,
            'error' => 'Cours non trouvé'
        ];
    }

    /**
     * Récupérer tous les cours d'un enseignant
     */
    public function getByEnseignant($idEnseignant)
    {
        $cours = $this->coursDAO->findAll(['id_enseignant' => $idEnseignant]);

        return [
            'success' => true,
            'cours' => $cours
        ];
    }

    /**
     * Récupérer les cours d'un enseignant avec statistiques (via findByEnseignant)
     */
    public function getMesCours($idEnseignant, $filters = [])
    {
        $cours = $this->coursDAO->findByEnseignant($idEnseignant, $filters);

        return [
            'success' => true,
            'cours' => $cours
        ];
    }

    /**
     * Récupérer tous les cours (pour admin)
     */
    public function getAll($filters = [])
    {
        $cours = $this->coursDAO->findAll($filters);

        return [
            'success' => true,
            'cours' => $cours
        ];
    }

    /**
     * Mettre à jour un cours
     */
    public function update($id, $data)
    {
        $cours = $this->coursDAO->findById($id);

        if (!$cours) {
            return [
                'success' => false,
                'error' => 'Cours non trouvé'
            ];
        }

        // Mettre à jour les propriétés
        $cours->nom_cours = $data['nom_cours'];
        $cours->code_cours = $data['code_cours'];
        $cours->description = $data['description'] ?? '';
        $cours->niveau = $data['niveau'];
        $cours->specialite = $data['specialite'];

        // Validation
        $errors = $cours->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Vérifier le code cours (sauf pour le cours actuel)
        if ($this->coursDAO->codeExists($cours->code_cours, $id)) {
            return [
                'success' => false,
                'error' => 'Ce code de cours existe déjà'
            ];
        }

        if ($this->coursDAO->update($cours)) {
            return [
                'success' => true,
                'message' => 'Cours mis à jour avec succès'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la mise à jour'
        ];
    }

    /**
     * Archiver un cours
     */
    public function archive($id)
    {
        if ($this->coursDAO->archive($id)) {
            return [
                'success' => true,
                'message' => 'Cours archivé avec succès'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de l\'archivage'
        ];
    }

    /**
     * Supprimer un cours
     */
    public function delete($id)
    {
        if ($this->coursDAO->delete($id)) {
            return [
                'success' => true,
                'message' => 'Cours supprimé avec succès'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la suppression'
        ];
    }

    /**
     * Récupérer les étudiants inscrits à un cours
     */
    public function getEtudiants($idCours)
    {
        $etudiants = $this->inscriptionDAO->findByCours($idCours);

        return [
            'success' => true,
            'etudiants' => $etudiants
        ];
    }

    /**
     * Compter les étudiants qui seront inscrits selon les critères
     */
    public function countEtudiants($niveau, $specialite, $anneeScolaire)
    {
        $count = $this->inscriptionDAO->countEtudiantsEligibles($niveau, $specialite, $anneeScolaire);

        return [
            'success' => true,
            'count' => $count
        ];
    }
}

// ========== Point d'entrée pour les requêtes HTTP ==========
// Exécuter uniquement si ce fichier est appelé directement
if (basename($_SERVER['PHP_SELF']) === 'CoursController.php' && isset($_GET['action'])) {
    header('Content-Type: application/json');

    $controller = new CoursController();
    $action = $_GET['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
                // Créer un cours
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Log des données reçues (pour debug)
                    error_log('CoursController - Données reçues: ' . print_r($_POST, true));
                    $result = $controller->create($_POST);
                    error_log('CoursController - Résultat: ' . print_r($result, true));
                    echo json_encode($result);
                }
                break;

            case 'countEtudiants':
                // Compter les étudiants éligibles
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $data = json_decode(file_get_contents('php://input'), true);
                    $result = $controller->countEtudiants(
                        $data['niveau'] ?? '',
                        $data['specialite'] ?? '',
                        $data['annee_scolaire'] ?? ''
                    );
                    echo json_encode($result);
                }
                break;

            default:
                echo json_encode([
                    'success' => false,
                    'error' => 'Action non reconnue'
                ]);
                break;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur serveur: ' . $e->getMessage()
        ]);
    }
    exit;
}
