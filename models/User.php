<?php

class User {

    // Private properties (safe from accidental overwrites)
    private $id_utilisateur;
    private $nom;
    private $prenom;
    private $email;
    private $mot_de_passe;
    private $type_utilisateur;
    private $numero_telephone;
    private $date_inscription;

    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->fromArray($data);
        }
    }

    /**
     * Safer alternative to hydrate()
     */
    public function fromArray(array $data) {

        if (isset($data['id_utilisateur'])) {
            $this->id_utilisateur = (int)$data['id_utilisateur'];
        }

        if (isset($data['nom'])) {
            $this->setNom($data['nom']);
        }

        if (isset($data['prenom'])) {
            $this->setPrenom($data['prenom']);
        }

        if (isset($data['email'])) {
            $this->setEmail($data['email']);
        }

        if (isset($data['mot_de_passe'])) {
            $this->mot_de_passe = $data['mot_de_passe']; // hashed from DB
        }

        if (isset($data['type_utilisateur'])) {
            $this->type_utilisateur = $data['type_utilisateur'];
        }

        if (isset($data['numero_telephone'])) {
            $this->numero_telephone = $data['numero_telephone'];
        }

        if (isset($data['date_inscription'])) {
            $this->date_inscription = $data['date_inscription'];
        }
    }

    // -------- GETTERS ----------

    public function getId() { return $this->id_utilisateur; }
    public function getNom() { return $this->nom; }
    public function getPrenom() { return $this->prenom; }
    public function getEmail() { return $this->email; }
    public function getTypeUtilisateur() { return $this->type_utilisateur; }
    public function getNomComplet() { return $this->prenom . ' ' . $this->nom; }
    public function getMotDePasse() { return $this->mot_de_passe ?? null;} // plaintext only for create(), DB stores hashed
    public function getNumeroTelephone() {return $this->numero_telephone ?? null;}
    

    // -------- SETTERS ----------

    public function setId($id) { $this->id_utilisateur = (int)$id; }

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
        $this->mot_de_passe = $password;
    }

    public function setTypeUtilisateur($type) {
        $this->type_utilisateur = $type;
    }

    public function setNumeroTelephone($tel) {
        $this->numero_telephone = htmlspecialchars(trim($tel));
    }
    /** * Vérifie si l'utilisateur est étudiant */ 
    public function isEtudiant() {
         return $this->type_utilisateur === 'etudiant'; 
        } 
    /** * Vérifie si l'utilisateur est enseignant */ 
    public function isEnseignant() { 
        return $this->type_utilisateur === 'enseignant'; 
    } 
    /** * Vérifie si l'utilisateur est administrateur */ 
    public function isAdministrateur() { 
        return $this->type_utilisateur === 'administrateur'; 
    }

    // -------- VALIDATION ----------

    public function validate() {

        $errors = [];

        // if (empty($this->nom)) {
        //     $errors[] = "Le nom est requis";
        // }

        // if (empty($this->prenom)) {
        //     $errors[] = "Le prénom est requis";
        // }

        // if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
        //     $errors[] = "Email invalide";
        // }

        // if (empty($this->type_utilisateur)) {
        //     $errors[] = "Le type d'utilisateur est requis";
        // }

        return $errors;
    }

    // -------- EXPORT ----------

    public function toArray() {
        return [
            'id_utilisateur' => $this->id_utilisateur,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'type_utilisateur' => $this->type_utilisateur,
            'numero_telephone' => $this->numero_telephone,
            'date_inscription' => $this->date_inscription
        ];
    }
}
