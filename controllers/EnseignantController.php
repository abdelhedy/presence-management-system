<?php
require_once __DIR__ . '/../dao/EnseignantDAO.php';
require_once __DIR__ . '/../dao/CoursDAO.php';
require_once __DIR__ . '/../dao/SeanceDAO.php';
require_once __DIR__ . '/../dao/PresenceDAO.php';

/**
 * EnseignantController - Gestion des actions enseignant
 */
class EnseignantController
{
    /** @var EnseignantDAO */
    private $enseignantDAO;

    /** @var CoursDAO */
    private $coursDAO;

    /** @var SeanceDAO */
    private $seanceDAO;

    /** @var PresenceDAO */
    private $presenceDAO;

    public function __construct()
    {
        $this->enseignantDAO = new EnseignantDAO();
        $this->coursDAO = new CoursDAO();
        $this->seanceDAO = new SeanceDAO();
        $this->presenceDAO = new PresenceDAO();
    }

    /**
     * Récupérer les données du dashboard
     */
    public function getDashboardData($idEnseignant)
    {
        return [
            'enseignant' => $this->enseignantDAO->findById($idEnseignant),
            'cours' => $this->enseignantDAO->getMesCours($idEnseignant)
        ];
    }

    /**
     * Créer un cours
     */
    public function creerCours($data, $idEnseignant)
    {
        $cours = new Cours();
        $cours->nom_cours = $data['nom_cours'];
        $cours->code_cours = $data['code_cours'];
        $cours->id_enseignant = $idEnseignant;
        $cours->description = $data['description'] ?? '';
        $cours->niveau = $data['niveau'];
        $cours->specialite = $data['specialite'];
        $cours->annee_scolaire = $data['annee_scolaire'];

        // Valider
        $errors = $cours->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Vérifier si le code existe
        if ($this->coursDAO->codeExists($cours->code_cours)) {
            return [
                'success' => false,
                'error' => 'Ce code de cours existe déjà.'
            ];
        }

        // Créer le cours
        if ($this->coursDAO->create($cours)) {
            return [
                'success' => true,
                'message' => 'Cours créé avec succès !'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la création du cours.'
        ];
    }

    /**
     * Créer une séance
     */
    public function creerSeance($data)
    {
        $seance = new Seance();
        $seance->id_cours = $data['id_cours'];
        $seance->date_seance = $data['date_seance'];
        $seance->heure_debut = $data['heure_debut'];
        $seance->heure_fin = $data['heure_fin'];
        $seance->salle = $data['salle'];
        $seance->type_seance = $data['type_seance'];

        // Valider
        $errors = $seance->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Créer la séance
        if ($this->seanceDAO->create($seance)) {
            return [
                'success' => true,
                'message' => 'Séance créée avec succès !'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la création de la séance.'
        ];
    }

    /**
     * Consulter les présences
     */
    public function consulterPresences($idCours, $date)
    {
        $presences = $this->presenceDAO->getPresencesByCours($idCours, $date);

        // Calculer les stats
        $total = count($presences);
        $presents = 0;
        $absents = 0;

        foreach ($presences as $p) {
            if ($p['statut'] == 'present') {
                $presents++;
            } else {
                $absents++;
            }
        }

        $taux = $total > 0 ? round(($presents / $total) * 100) : 0;

        return [
            'presences' => $presences,
            'stats' => [
                'total' => $total,
                'presents' => $presents,
                'absents' => $absents,
                'taux' => $taux
            ]
        ];
    }

    /**
     * Consulter les présences d'une séance
     */
    public function consulterPresencesSeance($idSeance)
    {
        $presences = $this->presenceDAO->getListePresenceSeance($idSeance);

        // Calculer les stats
        $total = count($presences);
        $presents = 0;

        foreach ($presences as $p) {
            if ($p['statut'] == 'present') {
                $presents++;
            }
        }

        $absents = $total - $presents;
        $taux = $total > 0 ? round(($presents / $total) * 100) : 0;

        // Formater les données pour l'affichage
        $presencesFormatted = [];
        foreach ($presences as $p) {
            $presencesFormatted[] = [
                'id_etudiant' => $p['id_etudiant'],
                'numero_etudiant' => $p['numero_etudiant'],
                'nom_complet' => $p['nom'] . ' ' . $p['prenom'],
                'email' => $p['email'],
                'statut' => $p['statut'],
                'heure_marquage' => $p['date_heure_marquage'],
                'methode_validation' => $p['methode_validation'],
                'score_reconnaissance' => $p['score_reconnaissance']
            ];
        }

        return [
            'success' => true,
            'presences' => $presencesFormatted,
            'stats' => [
                'total' => $total,
                'presents' => $presents,
                'absents' => $absents,
                'taux' => $taux
            ]
        ];
    }

    /**
     * Modifier un cours
     */
    public function modifierCours($data, $idCours, $idEnseignant)
    {
        $cours = $this->coursDAO->findById($idCours);

        if (!$cours || $cours->id_enseignant != $idEnseignant) {
            return [
                'success' => false,
                'error' => 'Cours non trouvé ou accès non autorisé.'
            ];
        }

        $cours->nom_cours = $data['nom_cours'];
        $cours->code_cours = $data['code_cours'];
        $cours->description = $data['description'] ?? '';
        $cours->niveau = $data['niveau'];
        $cours->specialite = $data['specialite'];

        // Vérifier le code (sauf pour ce cours)
        if ($this->coursDAO->codeExists($cours->code_cours, $idCours)) {
            return [
                'success' => false,
                'error' => 'Ce code de cours existe déjà.'
            ];
        }

        if ($this->coursDAO->update($cours)) {
            return [
                'success' => true,
                'message' => 'Cours modifié avec succès !'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de la modification.'
        ];
    }

    /**
     * Archiver un cours
     */
    public function archiverCours($idCours, $idEnseignant)
    {
        $cours = $this->coursDAO->findById($idCours);

        if (!$cours || $cours->id_enseignant != $idEnseignant) {
            return [
                'success' => false,
                'error' => 'Cours non trouvé ou accès non autorisé.'
            ];
        }

        if ($this->coursDAO->archive($idCours)) {
            return [
                'success' => true,
                'message' => 'Cours archivé avec succès !'
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de l\'archivage.'
        ];
    }
}
