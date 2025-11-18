<?php
/**
 * Classe métier User
 * Représente un utilisateur du système (abstrait)
 */
class User {
    // Propriétés
    public $id_utilisateur;
    public $nom;
    public $prenom;
    public $email;
    public $mot_de_passe;
    public $type_utilisateur; // etudiant, enseignant, administrateur
    public $numero_telephone;
    public $statut; // actif, inactif, suspendu
    public $date_inscription;
    
    // Constructeur
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    /**
     * Hydrate l'objet avec un tableau de données
     */
    public function hydrate($data) {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                $this->$key = $value;
            }
        }
    }
    
    // Getters
    public function getId() {
        return $this->id_utilisateur;
    }
    
    public function getNom() {
        return $this->nom;
    }
    
    public function getPrenom() {
        return $this->prenom;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getTypeUtilisateur() {
        return $this->type_utilisateur;
    }
    
    public function getStatut() {
        return $this->statut;
    }
    
    public function getNomComplet() {
        return $this->prenom . ' ' . $this->nom;
    }
    
    // Setters
    public function setId($id) {
        $this->id_utilisateur = (int)$id;
    }
    
    public function setIdUtilisateur($id) {
        $this->id_utilisateur = (int)$id;
    }
    
    public function setNom($nom) {
        $this->nom = htmlspecialchars(trim($nom));
    }
    
    public function setPrenom($prenom) {
        $this->prenom = htmlspecialchars(trim($prenom));
    }
    
    public function setEmail($email) {
        $this->email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    public function setMotDePasse($password) {
        $this->mot_de_passe = $password; // Sera hashé dans le DAO
    }
    
    public function setTypeUtilisateur($type) {
        $this->type_utilisateur = $type;
    }
    
    public function setNumeroTelephone($tel) {
        $this->numero_telephone = htmlspecialchars(trim($tel));
    }
    
    public function setStatut($statut) {
        $this->statut = $statut;
    }
    
    public function setDateInscription($date) {
        $this->date_inscription = $date;
    }
    
    /**
     * Vérifie si l'utilisateur est actif
     */
    public function isActif() {
        return $this->statut === 'actif';
    }
    
    /**
     * Vérifie si l'utilisateur est étudiant
     */
    public function isEtudiant() {
        return $this->type_utilisateur === 'etudiant';
    }
    
    /**
     * Vérifie si l'utilisateur est enseignant
     */
    public function isEnseignant() {
        return $this->type_utilisateur === 'enseignant';
    }
    
    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public function isAdministrateur() {
        return $this->type_utilisateur === 'administrateur';
    }
    
    /**
     * Valide les données avant insertion/update
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->nom)) {
            $errors[] = "Le nom est requis";
        }
        
        if (empty($this->prenom)) {
            $errors[] = "Le prénom est requis";
        }
        
        if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide";
        }
        
        if (empty($this->type_utilisateur)) {
            $errors[] = "Le type d'utilisateur est requis";
        }
        
        return $errors;
    }
    
    /**
     * Retourne un tableau associatif
     */
    public function toArray() {
        return [
            'id_utilisateur' => $this->id_utilisateur,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'type_utilisateur' => $this->type_utilisateur,
            'numero_telephone' => $this->numero_telephone,
            'statut' => $this->statut,
            'date_inscription' => $this->date_inscription
        ];
    }
}
?>