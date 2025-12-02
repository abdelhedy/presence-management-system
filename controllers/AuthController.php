<?php
require_once __DIR__ . '/../dao/UserDAO.php';
require_once __DIR__ . '/../dao/EtudiantDAO.php';
require_once __DIR__ . '/../dao/EnseignantDAO.php';

/**
 * AuthController - Gestion de l'authentification
 */
class AuthController
{
    private $userDAO;
    private $etudiantDAO;
    private $enseignantDAO;

    public function __construct()
    {
        $this->userDAO = new UserDAO();
        $this->etudiantDAO = new EtudiantDAO();
        $this->enseignantDAO = new EnseignantDAO();
    }

    /**
     * Connexion utilisateur
     */
    public function login($email, $password)
    {
        // Vérifier les identifiants
        $user = $this->userDAO->login($email, $password);

        if ($user) {
            // Créer la session
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['user_name'] = $user->getNomComplet();
            $_SESSION['user_type'] = $user->getTypeUtilisateur();
            $_SESSION['user_email'] = $user->getEmail();

            // Récupérer l'ID spécifique selon le type
            if ($user->isEtudiant()) {
                $etudiant = $this->etudiantDAO->findByUserId($user->getId());
                if ($etudiant) {
                    $_SESSION['etudiant_id'] = $etudiant->id_etudiant;
                }
            } elseif ($user->isEnseignant()) {
                $enseignant = $this->enseignantDAO->findByUserId($user->getId());
                if ($enseignant) {
                    $_SESSION['enseignant_id'] = $enseignant->id_enseignant;
                }
            }

            return [
                'success' => true,
                'redirect' => $this->getRedirectUrl($user->getTypeUtilisateur())
            ];
        }

        return [
            'success' => false,
            'error' => 'Email ou mot de passe incorrect, ou compte inactif.'
        ];
    }

    /**
     * Inscription utilisateur
     */
    public function register($data)
    {
        // Créer l'objet User
        // Créer l'objet User
        $user = new User();
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setEmail($data['email']);
        $user->setMotDePasse($data['password']);
        $user->setTypeUtilisateur($data['type_utilisateur']);
        $user->setNumeroTelephone($data['telephone'] ?? '');


        // DEBUG: Check what values are set in the User object
        // error_log("User object before validation:");
        // error_log("Nom: " . $user->ge);
        // error_log("Prenom: " . $user->prenom);
        // error_log("Email: " . $user->email);
        // error_log("Type: " . $user->type_utilisateur);

        // Valider les données
        $errors = $user->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // DEBUG: Check validation results
        error_log("Validation errors: " . print_r($errors, true));

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Vérifier si l'email existe déjà
        if ($this->userDAO->emailExists($user->getEmail())) {
            return ['success' => false, 'error' => 'Cet email est déjà utilisé.'];
        }

        // Créer l'utilisateur
        $userId = $this->userDAO->create($user);

        if (!$userId) {
            return ['success' => false, 'error' => 'Erreur lors de la création du compte.'];
        }

        // Créer le profil spécifique
        if ($user->getTypeUtilisateur() === 'etudiant') {
            return $this->registerEtudiant($userId, $data);
        } elseif ($user->getTypeUtilisateur() === 'enseignant') {
            return $this->registerEnseignant($userId, $data);
        }

        return [
            'success' => true,
            'message' => 'Inscription réussie !',
            'redirect' => $this->getRedirectUrl($user->getTypeUtilisateur())
        ];
    }

    /**
     * Inscription spécifique étudiant
     */
    private function registerEtudiant($userId, $data)
    {
        $etudiant = new Etudiant();
        $etudiant->setId($userId);
        $etudiant->numero_etudiant = $data['numero_etudiant'];
        $etudiant->niveau = $data['niveau'];
        $etudiant->specialite = $data['specialite'];
        $etudiant->annee_scolaire = $data['annee_scolaire'];

        // Valider
        $errors = $etudiant->validate();
        if (!empty($errors)) {
            // Supprimer l'utilisateur créé
            $this->userDAO->delete($userId);
            return ['success' => false, 'errors' => $errors];
        }

        // Vérifier si le numéro existe
        if ($this->etudiantDAO->numeroExists($etudiant->numero_etudiant)) {
            $this->userDAO->delete($userId);
            return ['success' => false, 'error' => 'Ce numéro étudiant est déjà utilisé.'];
        }

        // Créer le profil étudiant
        $etudiantId = $this->etudiantDAO->create($etudiant);

        if ($etudiantId) {
            // Auto-login après inscription
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $data['nom'] . ' ' . $data['prenom'];
            $_SESSION['user_type'] = 'etudiant';
            $_SESSION['user_email'] = $data['email'];
            $_SESSION['etudiant_id'] = $etudiantId;

            return [
                'success' => true,
                'message' => 'Inscription réussie !',
                'redirect' => 'etudiant/dashboard.php'
            ];
        }

        // En cas d'erreur, supprimer l'utilisateur
        $this->userDAO->delete($userId);
        return ['success' => false, 'error' => 'Erreur lors de la création du profil étudiant.'];
    }

    /**
     * Inscription spécifique enseignant
     */
    private function registerEnseignant($userId, $data)
    {
        $enseignant = new Enseignant();
        $enseignant->setId($userId);
        $enseignant->departement = $data['departement'];
        $enseignant->grade = $data['grade'];

        // Valider
        $errors = $enseignant->validate();
        if (!empty($errors)) {
            $this->userDAO->delete($userId);
            return ['success' => false, 'errors' => $errors];
        }

        // Créer le profil enseignant
        $enseignantId = $this->enseignantDAO->create($enseignant);

        if ($enseignantId) {
            // Auto-login après inscription
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $data['nom'] . ' ' . $data['prenom'];
            $_SESSION['user_type'] = 'enseignant';
            $_SESSION['user_email'] = $data['email'];
            $_SESSION['enseignant_id'] = $enseignantId;

            return [
                'success' => true,
                'message' => 'Inscription réussie !',
                'redirect' => 'enseignant/dashboard.php'
            ];
        }

        $this->userDAO->delete($userId);
        return ['success' => false, 'error' => 'Erreur lors de la création du profil enseignant.'];
    }

    /**
     * Déconnexion
     */
    public function logout()
    {
        session_unset();
        session_destroy();
        return ['success' => true, 'redirect' => 'index.php'];
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Vérifier le type d'utilisateur
     */
    public static function checkUserType($requiredType)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        if (is_array($requiredType)) {
            return in_array($_SESSION['user_type'], $requiredType);
        }

        return $_SESSION['user_type'] === $requiredType;
    }

    /**
     * Obtenir l'URL de redirection selon le type
     */
    private function getRedirectUrl($type)
    {
        switch ($type) {
            case 'etudiant':
                return 'etudiant/dashboard.php';
            case 'enseignant':
                return 'enseignant/dashboard.php';
            case 'administrateur':
                return 'admin/dashboard.php';
            default:
                return 'index.php';
        }
    }

    /**
     * Rediriger si non connecté
     */
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header("Location: ../login.php");
            exit();
        }
    }

    /**
     * Rediriger si mauvais type d'utilisateur
     */
    public static function requireUserType($requiredType)
    {
        self::requireLogin();

        if (!self::checkUserType($requiredType)) {
            header("Location: ../index.php");
            exit();
        }
    }
}
