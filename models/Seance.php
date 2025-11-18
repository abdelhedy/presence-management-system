<?php
/**
 * Classe métier Seance
 */
class Seance {
    public $id_seance;
    public $id_cours;
    public $date_seance;
    public $heure_debut;
    public $heure_fin;
    public $salle;
    public $type_seance;
    public $statut;
    
    // Propriétés calculées (from JOIN)
    public $nom_cours;
    public $code_cours;
    
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
        return $this->id_seance;
    }
    
    public function getIdCours() {
        return $this->id_cours;
    }
    
    public function getDateSeance() {
        return $this->date_seance;
    }
    
    public function isToday() {
        return $this->date_seance === date('Y-m-d');
    }
    
    // Setters
    public function setIdSeance($id) {
        $this->id_seance = (int)$id;
    }
    
    public function setIdCours($id) {
        $this->id_cours = (int)$id;
    }
    
    public function setDateSeance($date) {
        $this->date_seance = $date;
    }
    
    public function setHeureDebut($heure) {
        $this->heure_debut = $heure;
    }
    
    public function setHeureFin($heure) {
        $this->heure_fin = $heure;
    }
    
    public function setSalle($salle) {
        $this->salle = htmlspecialchars(trim($salle));
    }
    
    public function setTypeSeance($type) {
        $this->type_seance = $type;
    }
    
    public function setStatut($statut) {
        $this->statut = $statut;
    }
    
    public function setNomCours($nom) {
        $this->nom_cours = $nom;
    }
    
    public function setCodeCours($code) {
        $this->code_cours = $code;
    }
    
    /**
     * Validation
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->id_cours)) {
            $errors[] = "Le cours est requis";
        }
        
        if (empty($this->date_seance)) {
            $errors[] = "La date est requise";
        }
        
        if (empty($this->heure_debut) || empty($this->heure_fin)) {
            $errors[] = "Les horaires sont requis";
        }
        
        if ($this->heure_debut >= $this->heure_fin) {
            $errors[] = "L'heure de fin doit être après l'heure de début";
        }
        
        if (empty($this->salle)) {
            $errors[] = "La salle est requise";
        }
        
        return $errors;
    }
    
    public function toArray() {
        return [
            'id_seance' => $this->id_seance,
            'id_cours' => $this->id_cours,
            'date_seance' => $this->date_seance,
            'heure_debut' => $this->heure_debut,
            'heure_fin' => $this->heure_fin,
            'salle' => $this->salle,
            'type_seance' => $this->type_seance,
            'statut' => $this->statut
        ];
    }
}
?>