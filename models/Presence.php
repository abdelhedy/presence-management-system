<?php
/**
 * Classe métier Presence
 */
class Presence {
    public $id_presence;
    public $id_seance;
    public $id_etudiant;
    public $date_heure_marquage;
    public $statut;
    public $methode_validation;
    public $score_reconnaissance;
    
    // Propriétés calculées (from JOIN)
    public $nom_etudiant;
    public $prenom_etudiant;
    public $numero_etudiant;
    public $nom_cours;
    
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
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
        return $this->id_presence;
    }
    
    public function getStatut() {
        return $this->statut;
    }
    
    public function isPresent() {
        return $this->statut === 'present';
    }
    
    public function getScoreReconnaissance() {
        return $this->score_reconnaissance;
    }
    
    // Setters
    public function setIdPresence($id) {
        $this->id_presence = (int)$id;
    }
    
    public function setIdSeance($id) {
        $this->id_seance = (int)$id;
    }
    
    public function setIdEtudiant($id) {
        $this->id_etudiant = (int)$id;
    }
    
    public function setDateHeureMarquage($datetime) {
        $this->date_heure_marquage = $datetime;
    }
    
    public function setStatut($statut) {
        $this->statut = $statut;
    }
    
    public function setMethodeValidation($methode) {
        $this->methode_validation = $methode;
    }
    
    public function setScoreReconnaissance($score) {
        $this->score_reconnaissance = (float)$score;
    }
    
    public function setNomEtudiant($nom) {
        $this->nom_etudiant = $nom;
    }
    
    public function setPrenomEtudiant($prenom) {
        $this->prenom_etudiant = $prenom;
    }
    
    public function setNumeroEtudiant($numero) {
        $this->numero_etudiant = $numero;
    }
    
    public function setNomCours($nom) {
        $this->nom_cours = $nom;
    }
    
    /**
     * Validation
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->id_seance)) {
            $errors[] = "La séance est requise";
        }
        
        if (empty($this->id_etudiant)) {
            $errors[] = "L'étudiant est requis";
        }
        
        if (empty($this->statut)) {
            $errors[] = "Le statut est requis";
        }
        
        return $errors;
    }
    
    public function toArray() {
        return [
            'id_presence' => $this->id_presence,
            'id_seance' => $this->id_seance,
            'id_etudiant' => $this->id_etudiant,
            'date_heure_marquage' => $this->date_heure_marquage,
            'statut' => $this->statut,
            'methode_validation' => $this->methode_validation,
            'score_reconnaissance' => $this->score_reconnaissance
        ];
    }
}
?>