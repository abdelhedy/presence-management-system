<?php
/**
 * Classe métier Cours
 */
class Cours {
    // Propriétés
    public $id_cours;
    public $nom_cours;
    public $code_cours;
    public $id_enseignant;
    public $description;
    public $niveau;
    public $specialite;
    public $annee_scolaire;
    public $statut;
    public $date_creation;
    
    // Propriétés calculées (from JOIN)
    public $nom_enseignant;
    public $nb_etudiants;
    
    // Constructeur
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    /**
     * Hydrate l'objet
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
        return $this->id_cours;
    }
    
    public function getNomCours() {
        return $this->nom_cours;
    }
    
    public function getCodeCours() {
        return $this->code_cours;
    }
    
    public function getDescription() {
        return $this->description;
    }
    
    public function isActif() {
        return $this->statut === 'actif';
    }
    
    // Setters
    public function setIdCours($id) {
        $this->id_cours = (int)$id;
    }
    
    public function setNomCours($nom) {
        $this->nom_cours = htmlspecialchars(trim($nom));
    }
    
    public function setCodeCours($code) {
        $this->code_cours = strtoupper(htmlspecialchars(trim($code)));
    }
    
    public function setIdEnseignant($id) {
        $this->id_enseignant = (int)$id;
    }
    
    public function setDescription($desc) {
        $this->description = htmlspecialchars(trim($desc));
    }
    
    public function setNiveau($niveau) {
        $this->niveau = htmlspecialchars(trim($niveau));
    }
    
    public function setSpecialite($spec) {
        $this->specialite = htmlspecialchars(trim($spec));
    }
    
    public function setAnneeScolaire($annee) {
        $this->annee_scolaire = htmlspecialchars(trim($annee));
    }
    
    public function setStatut($statut) {
        $this->statut = $statut;
    }
    
    public function setDateCreation($date) {
        $this->date_creation = $date;
    }
    
    public function setNomEnseignant($nom) {
        $this->nom_enseignant = $nom;
    }
    
    public function setNbEtudiants($nb) {
        $this->nb_etudiants = (int)$nb;
    }
    
    /**
     * Validation
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->nom_cours)) {
            $errors[] = "Le nom du cours est requis";
        }
        
        if (empty($this->code_cours)) {
            $errors[] = "Le code du cours est requis";
        }
        
        if (empty($this->niveau)) {
            $errors[] = "Le niveau est requis";
        }
        
        if (empty($this->specialite)) {
            $errors[] = "La spécialité est requise";
        }
        
        return $errors;
    }
    
    /**
     * Retourne un tableau
     */
    public function toArray() {
        return [
            'id_cours' => $this->id_cours,
            'nom_cours' => $this->nom_cours,
            'code_cours' => $this->code_cours,
            'id_enseignant' => $this->id_enseignant,
            'description' => $this->description,
            'niveau' => $this->niveau,
            'specialite' => $this->specialite,
            'annee_scolaire' => $this->annee_scolaire,
            'statut' => $this->statut,
            'date_creation' => $this->date_creation
        ];
    }
}
?>