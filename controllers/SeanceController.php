<?php
require_once __DIR__ . '/../dao/SeanceDAO.php';
require_once __DIR__ . '/../dao/CoursDAO.php';
require_once __DIR__ . '/../models/Seance.php';

/**
 * SeanceController - Gestion des séances
 */
class SeanceController
{
    private $seanceDAO;
    private $coursDAO;

    public function __construct()
    {
        $this->seanceDAO = new SeanceDAO();
        $this->coursDAO = new CoursDAO();
    }

    /**
     * Créer une nouvelle séance
     */
    public function create($data)
    {
        // Créer l'objet Seance
        $seance = new Seance();
        $seance->id_cours = $data['id_cours'];
        $seance->date_seance = $data['date_seance'];
        $seance->heure_debut = $data['heure_debut'];
        $seance->heure_fin = $data['heure_fin'];
        $seance->salle = $data['salle'];
        $seance->type_seance = $data['type_seance'];

        // Validation
        $errors = $seance->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        // Vérifier que le cours existe
        $cours = $this->coursDAO->findById($seance->id_cours);
        if (!$cours) {
            return [
                'success' => false,
                'error' => 'Cours non trouvé'
            ];
        }

        // Créer la séance
        $seanceId = $this->seanceDAO->create($seance);

        if ($seanceId) {
            return [
                'success' => true,
                'message' => 'Séance créée avec succès',
                'seance_id' => $seanceId
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la création de la séance'
        ];
    }

    /**
     * Récupérer une séance par ID
     */
    public function getById($id)
    {
        $seance = $this->seanceDAO->findById($id);

        if ($seance) {
            return [
                'success' => true,
                'seance' => $seance
            ];
        }

        return [
            'success' => false,
            'error' => 'Séance non trouvée'
        ];
    }

    /**
     * Récupérer toutes les séances d'un cours
     */
    public function getByCours($idCours)
    {
        $seances = $this->seanceDAO->findByCours($idCours);

        return [
            'success' => true,
            'seances' => $seances
        ];
    }

    /**
     * Récupérer les séances d'un enseignant
     */
    public function getByEnseignant($idEnseignant, $filters = [])
    {
        $seances = $this->seanceDAO->findByEnseignant($idEnseignant, $filters);

        return [
            'success' => true,
            'seances' => $seances
        ];
    }

    /**
     * Récupérer les séances à venir d'un enseignant
     */
    public function getUpcomingByEnseignant($idEnseignant, $limit = 5)
    {
        $seances = $this->seanceDAO->findUpcomingByEnseignant($idEnseignant, $limit);

        return [
            'success' => true,
            'seances' => $seances
        ];
    }

    /**
     * Récupérer les séances d'un étudiant
     */
    public function getByEtudiant($idEtudiant, $filters = [])
    {
        $seances = $this->seanceDAO->findByEtudiant($idEtudiant, $filters);

        return [
            'success' => true,
            'seances' => $seances
        ];
    }

    /**
     * Récupérer les séances actives du jour pour un étudiant
     */
    public function getTodayActiveByEtudiant($idEtudiant)
    {
        $seances = $this->seanceDAO->findTodayActiveByEtudiant($idEtudiant);

        return [
            'success' => true,
            'seances' => $seances
        ];
    }

    /**
     * Mettre à jour une séance
     */
    public function update($id, $data)
    {
        $seance = $this->seanceDAO->findById($id);

        if (!$seance) {
            return [
                'success' => false,
                'error' => 'Séance non trouvée'
            ];
        }

        // Mettre à jour les propriétés
        $seance->date_seance = $data['date_seance'];
        $seance->heure_debut = $data['heure_debut'];
        $seance->heure_fin = $data['heure_fin'];
        $seance->salle = $data['salle'];
        $seance->type_seance = $data['type_seance'];

        // Validation
        $errors = $seance->validate();
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        if ($this->seanceDAO->update($seance)) {
            return [
                'success' => true,
                'message' => 'Séance mise à jour avec succès'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la mise à jour'
        ];
    }

    /**
     * Annuler une séance
     */
    public function annuler($id)
    {
        if ($this->seanceDAO->annuler($id)) {
            return [
                'success' => true,
                'message' => 'Séance annulée avec succès'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de l\'annulation'
        ];
    }

    /**
     * Supprimer une séance
     */
    public function delete($id)
    {
        if ($this->seanceDAO->delete($id)) {
            return [
                'success' => true,
                'message' => 'Séance supprimée avec succès'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la suppression'
        ];
    }

    /**
     * Obtenir les statistiques d'un enseignant
     */
    public function getStatsEnseignant($idEnseignant)
    {
        $stats = $this->seanceDAO->getStatsPresenceByEnseignant($idEnseignant);

        return [
            'success' => true,
            'stats' => $stats
        ];
    }

    /**
     * Compter les séances par statut pour un enseignant
     */
    public function countByEnseignant($idEnseignant, $statut = null)
    {
        $count = $this->seanceDAO->countByEnseignant($idEnseignant, $statut);

        return [
            'success' => true,
            'count' => $count
        ];
    }
}
