<?php
require_once 'User.php';

/**
 * Classe métier Etudiant
 * Hérite de User et ajoute les propriétés spécifiques
 */
class Etudiant extends User {
    // Propriétés spécifiques
    public $id_etudiant;
    public $numero_etudiant;
    public $niveau;
    public $specialite;
    public $annee_scolaire;
    
    // Constructeur
    public function __construct($data = []) {
        parent::__construct($data);
        $this->setTypeUtilisateur('etudiant') ;
    }
    
    // Getters
    public function getIdEtudiant() {
        return $this->id_etudiant;
    }
    
    public function getNumeroEtudiant() {
        return $this->numero_etudiant;
    }
    
    public function getNiveau() {
        return $this->niveau;
    }
    
    public function getSpecialite() {
        return $this->specialite;
    }
    
    public function getAnneeScolaire() {
        return $this->annee_scolaire;
    }
    
    // Setters
    public function setIdEtudiant($id) {
        $this->id_etudiant = (int)$id;
    }
    
    public function setNumeroEtudiant($numero) {
        $this->numero_etudiant = htmlspecialchars(trim($numero));
    }
    
    public function setNiveau($niveau) {
        $this->niveau = htmlspecialchars(trim($niveau));
    }
    
    public function setSpecialite($specialite) {
        $this->specialite = htmlspecialchars(trim($specialite));
    }
    
    public function setAnneeScolaire($annee) {
        $this->annee_scolaire = htmlspecialchars(trim($annee));
    }
    
    /**
     * Validation spécifique étudiant
     */
    public function validate() {
        $errors = parent::validate();
        
        // if (empty($this->numero_etudiant)) {
        //     $errors[] = "Le numéro étudiant est requis";
        // }
        
        // if (empty($this->niveau)) {
        //     $errors[] = "Le niveau est requis";
        // }
        
        // if (empty($this->specialite)) {
        //     $errors[] = "La spécialité est requise";
        // }
        
        return $errors;
    }
    
    /**
     * Retourne un tableau complet
     */
    public function toArray() {
        return array_merge(parent::toArray(), [
            'id_etudiant' => $this->id_etudiant,
            'numero_etudiant' => $this->numero_etudiant,
            'niveau' => $this->niveau,
            'specialite' => $this->specialite,
            'annee_scolaire' => $this->annee_scolaire
        ]);
    }
}
?>