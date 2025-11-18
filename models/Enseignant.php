<?php
require_once __DIR__ . '/User.php';

/**
 * Classe métier Enseignant
 * Hérite de User
 */
class Enseignant extends User {
    public $id_enseignant;
    public $departement;
    public $grade;
    
    public function __construct($data = []) {
        parent::__construct($data);
        $this->type_utilisateur = 'enseignant';
    }
    
    // Getters
    public function getIdEnseignant() {
        return $this->id_enseignant;
    }
    
    public function getDepartement() {
        return $this->departement;
    }
    
    public function getGrade() {
        return $this->grade;
    }
    
    // Setters
    public function setIdEnseignant($id) {
        $this->id_enseignant = (int)$id;
    }
    
    public function setDepartement($dept) {
        $this->departement = htmlspecialchars(trim($dept));
    }
    
    public function setGrade($grade) {
        $this->grade = htmlspecialchars(trim($grade));
    }
    
    /**
     * Validation spécifique enseignant
     */
    public function validate() {
        $errors = parent::validate();
        
        if (empty($this->departement)) {
            $errors[] = "Le département est requis";
        }
        
        if (empty($this->grade)) {
            $errors[] = "Le grade est requis";
        }
        
        return $errors;
    }
    
    /**
     * Retourne un tableau complet
     */
    public function toArray() {
        return array_merge(parent::toArray(), [
            'id_enseignant' => $this->id_enseignant,
            'departement' => $this->departement,
            'grade' => $this->grade
        ]);
    }
}
?>