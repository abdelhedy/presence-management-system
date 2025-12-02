<?php
require_once __DIR__ . '/../dao/EtudiantDAO.php';
require_once __DIR__ . '/../dao/SeanceDAO.php';
require_once __DIR__ . '/../dao/PresenceDAO.php';

/**
 * EtudiantController - Gestion des actions étudiant
 */
class EtudiantController
{
    private $etudiantDAO;
    private $seanceDAO;
    private $presenceDAO;

    public function __construct()
    {
        $this->etudiantDAO = new EtudiantDAO();
        $this->seanceDAO = new SeanceDAO();
        $this->presenceDAO = new PresenceDAO();
    }

    /**
     * Récupérer les données du dashboard
     */
    public function getDashboardData($idEtudiant)
    {
        return [
            'etudiant' => $this->etudiantDAO->findById($idEtudiant),
            'cours' => $this->etudiantDAO->getMesCours($idEtudiant),
            'seances_today' => $this->seanceDAO->getSeancesToday($idEtudiant),
            'stats' => $this->presenceDAO->getStatsByEtudiant($idEtudiant),
            'has_profile_image' => $this->etudiantDAO->hasProfileImage($idEtudiant)
        ];
    }

    /**
     * Récupérer les cours de l'étudiant
     */
    public function getMesCours($idEtudiant)
    {
        require_once __DIR__ . '/../dao/InscriptionDAO.php';
        $inscriptionDAO = new InscriptionDAO();

        $cours = $inscriptionDAO->findByEtudiant($idEtudiant);

        return [
            'success' => true,
            'cours' => $cours
        ];
    }

    /**
     * Récupérer un étudiant par ID
     */
    public function getById($idEtudiant)
    {
        $etudiant = $this->etudiantDAO->findById($idEtudiant);

        if ($etudiant) {
            return [
                'success' => true,
                'etudiant' => $etudiant
            ];
        }

        return [
            'success' => false,
            'error' => 'Étudiant non trouvé'
        ];
    }

    /**
     * Marquer la présence
     */
    public function marquerPresence($idSeance, $idEtudiant, $imageFile = null)
    {
        // Vérifier si déjà marqué
        if ($this->presenceDAO->exists($idSeance, $idEtudiant)) {
            return [
                'success' => false,
                'error' => 'Vous avez déjà marqué votre présence pour cette séance.'
            ];
        }

        // Créer la présence
        $presence = new Presence();
        $presence->id_seance = $idSeance;
        $presence->id_etudiant = $idEtudiant;
        $presence->statut = 'present';
        $presence->methode_validation = 'image';

        // Simuler la reconnaissance faciale
        if ($imageFile) {
            $presence->score_reconnaissance = $this->simulateRecognition();
        }

        if ($this->presenceDAO->create($presence)) {
            return [
                'success' => true,
                'message' => 'Présence marquée avec succès !',
                'score' => $presence->score_reconnaissance
            ];
        }

        return [
            'success' => false,
            'error' => 'Erreur lors du marquage de présence.'
        ];
    }

    /**
     * Upload image de profil
     */
    public function uploadProfileImage($idEtudiant, $file)
    {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];

        if (!in_array($file['type'], $allowed_types)) {
            return [
                'success' => false,
                'error' => 'Format de fichier non autorisé. Utilisez JPG, JPEG ou PNG.'
            ];
        }

        if ($file['size'] > 5242880) { // 5MB
            return [
                'success' => false,
                'error' => 'Le fichier est trop volumineux. Taille maximale : 5MB.'
            ];
        }

        $upload_dir = __DIR__ . '/../uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'etudiant_' . $idEtudiant . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Enregistrer dans la BD
            $database = new Database();
            $conn = $database->getConnection();

            $query = "INSERT INTO images_reference (id_etudiant, chemin_image, encodage_facial) 
                      VALUES (:id, :chemin, :encodage)
                      ON DUPLICATE KEY UPDATE 
                      chemin_image = :chemin, 
                      encodage_facial = :encodage,
                      date_ajout = CURRENT_TIMESTAMP";

            $stmt = $conn->prepare($query);
            $encodage = "SIMULATED_ENCODING_" . time();

            $stmt->bindParam(':id', $idEtudiant);
            $stmt->bindParam(':chemin', $upload_path);
            $stmt->bindParam(':encodage', $encodage);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Image de profil ajoutée avec succès !'
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Erreur lors de l\'upload du fichier.'
        ];
    }

    /**
     * Simuler la reconnaissance faciale (à remplacer par API Python)
     */
    private function simulateRecognition()
    {
        return round(rand(85, 98) / 100, 2);
    }
}
