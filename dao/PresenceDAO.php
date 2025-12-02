<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Presence.php';

/**
 * PresenceDAO - Gestion des opérations BD pour Presence
 */
class PresenceDAO
{
    private $conn;
    private $table = "presences";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Marquer une présence
     */
    public function create(Presence $presence)
    {
        // Vérifier si une présence existe déjà
        if ($this->exists($presence->id_seance, $presence->id_etudiant)) {
            return $this->update($presence);
        }

        $query = "INSERT INTO " . $this->table . " 
                  SET id_seance = :id_seance,
                      id_etudiant = :id_etudiant,
                      date_heure_marquage = NOW(),
                      statut = :statut,
                      methode_validation = :methode,
                      score_reconnaissance = :score";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_seance", $presence->id_seance);
        $stmt->bindParam(":id_etudiant", $presence->id_etudiant);
        $stmt->bindParam(":statut", $presence->statut);
        $stmt->bindParam(":methode", $presence->methode_validation);
        $stmt->bindParam(":score", $presence->score_reconnaissance);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Vérifier si une présence existe
     */
    public function exists($idSeance, $idEtudiant)
    {
        $query = "SELECT id_presence FROM " . $this->table . " 
                  WHERE id_seance = :id_seance 
                  AND id_etudiant = :id_etudiant 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_seance", $idSeance);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Mettre à jour une présence existante
     */
    public function update(Presence $presence)
    {
        $query = "UPDATE " . $this->table . " 
                  SET statut = :statut,
                      date_heure_marquage = NOW(),
                      methode_validation = :methode,
                      score_reconnaissance = :score
                  WHERE id_seance = :id_seance 
                  AND id_etudiant = :id_etudiant";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":statut", $presence->statut);
        $stmt->bindParam(":methode", $presence->methode_validation);
        $stmt->bindParam(":score", $presence->score_reconnaissance);
        $stmt->bindParam(":id_seance", $presence->id_seance);
        $stmt->bindParam(":id_etudiant", $presence->id_etudiant);

        return $stmt->execute();
    }

    /**
     * Récupérer les présences par cours et date
     */
    public function getPresencesByCours($idCours, $date = null)
    {
        $query = "SELECT p.*, 
                  e.numero_etudiant,
                  u.nom, u.prenom,
                  s.date_seance, s.heure_debut, s.heure_fin
                  FROM " . $this->table . " p
                  JOIN etudiants e ON p.id_etudiant = e.id_etudiant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  JOIN seances s ON p.id_seance = s.id_seance
                  WHERE s.id_cours = :id_cours";

        if ($date) {
            $query .= " AND s.date_seance = :date";
        }

        $query .= " ORDER BY s.date_seance DESC, s.heure_debut DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_cours", $idCours);

        if ($date) {
            $stmt->bindParam(":date", $date);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer une présence par ID
     */
    public function findById($id)
    {
        $query = "SELECT p.*, 
                  e.numero_etudiant,
                  u.nom, u.prenom,
                  c.nom_cours
                  FROM " . $this->table . " p
                  JOIN etudiants e ON p.id_etudiant = e.id_etudiant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  JOIN seances s ON p.id_seance = s.id_seance
                  JOIN cours c ON s.id_cours = c.id_cours
                  WHERE p.id_presence = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return new Presence($stmt->fetch(PDO::FETCH_ASSOC));
        }
        return null;
    }

    /**
     * Récupérer toutes les présences d'une séance
     */
    public function findBySeance($idSeance)
    {
        $query = "SELECT p.*, 
                  e.numero_etudiant,
                  u.nom, u.prenom, u.email
                  FROM " . $this->table . " p
                  JOIN etudiants e ON p.id_etudiant = e.id_etudiant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  WHERE p.id_seance = :id_seance
                  ORDER BY u.nom, u.prenom";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_seance", $idSeance);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer l'historique de présence d'un étudiant
     */
    public function findByEtudiant($idEtudiant, $filters = [])
    {
        $query = "SELECT p.*, 
                  s.date_seance, s.heure_debut, s.heure_fin, s.type_seance,
                  c.nom_cours, c.code_cours
                  FROM " . $this->table . " p
                  JOIN seances s ON p.id_seance = s.id_seance
                  JOIN cours c ON s.id_cours = c.id_cours
                  WHERE p.id_etudiant = :id_etudiant";

        if (!empty($filters['statut'])) {
            $query .= " AND p.statut = :statut";
        }

        if (!empty($filters['date_debut'])) {
            $query .= " AND s.date_seance >= :date_debut";
        }

        if (!empty($filters['date_fin'])) {
            $query .= " AND s.date_seance <= :date_fin";
        }

        if (!empty($filters['id_cours'])) {
            $query .= " AND c.id_cours = :id_cours";
        }

        $query .= " ORDER BY s.date_seance DESC, s.heure_debut DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);

        if (!empty($filters['statut'])) {
            $stmt->bindParam(':statut', $filters['statut']);
        }
        if (!empty($filters['date_debut'])) {
            $stmt->bindParam(':date_debut', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $stmt->bindParam(':date_fin', $filters['date_fin']);
        }
        if (!empty($filters['id_cours'])) {
            $stmt->bindParam(':id_cours', $filters['id_cours']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les statistiques de présence d'un étudiant
     */
    public function getStatsByEtudiant($idEtudiant, $idCours = null)
    {
        $query = "SELECT 
                  COUNT(*) as total_seances,
                  SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as total_presents,
                  SUM(CASE WHEN p.statut = 'absent' THEN 1 ELSE 0 END) as total_absents,
                  ROUND(
                      (SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) * 100.0) / 
                      NULLIF(COUNT(*), 0), 
                      2
                  ) as taux_presence
                  FROM " . $this->table . " p
                  JOIN seances s ON p.id_seance = s.id_seance
                  WHERE p.id_etudiant = :id_etudiant";

        if ($idCours) {
            $query .= " AND s.id_cours = :id_cours";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);

        if ($idCours) {
            $stmt->bindParam(":id_cours", $idCours);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les statistiques par cours pour un étudiant
     */
    public function getStatsByCours($idEtudiant)
    {
        $query = "SELECT 
                  c.id_cours,
                  c.nom_cours,
                  c.code_cours,
                  COUNT(*) as total_seances,
                  SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as total_presents,
                  SUM(CASE WHEN p.statut = 'absent' THEN 1 ELSE 0 END) as total_absents,
                  ROUND(
                      (SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) * 100.0) / 
                      NULLIF(COUNT(*), 0), 
                      2
                  ) as taux_presence
                  FROM " . $this->table . " p
                  JOIN seances s ON p.id_seance = s.id_seance
                  JOIN cours c ON s.id_cours = c.id_cours
                  WHERE p.id_etudiant = :id_etudiant
                  GROUP BY c.id_cours
                  ORDER BY c.nom_cours";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_etudiant", $idEtudiant);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les étudiants présents/absents d'une séance avec détails
     */
    public function getListePresenceSeance($idSeance)
    {
        $query = "SELECT 
                  i.id_etudiant,
                  e.numero_etudiant,
                  u.nom, u.prenom, u.email,
                  p.id_presence,
                  COALESCE(p.statut, 'non_marque') as statut,
                  p.date_heure_marquage,
                  p.methode_validation,
                  p.score_reconnaissance
                  FROM inscriptions i
                  JOIN seances s ON i.id_cours = s.id_cours
                  JOIN etudiants e ON i.id_etudiant = e.id_etudiant
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  LEFT JOIN " . $this->table . " p ON p.id_seance = s.id_seance 
                                                    AND p.id_etudiant = i.id_etudiant
                  WHERE s.id_seance = :id_seance
                  AND i.statut = 'inscrit'
                  ORDER BY u.nom, u.prenom";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_seance", $idSeance);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Supprimer une présence
     */
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id_presence = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    /**
     * Marquer manuellement la présence/absence (pour l'enseignant)
     */
    public function marquerManuel($idSeance, $idEtudiant, $statut)
    {
        $presence = new Presence();
        $presence->id_seance = $idSeance;
        $presence->id_etudiant = $idEtudiant;
        $presence->statut = $statut;
        $presence->methode_validation = 'manuel';
        $presence->score_reconnaissance = null;

        return $this->create($presence);
    }
    
    // ========== MÉTHODES ADMIN ==========

    /**
     * Statistiques globales de présence (admin)
     */
    public function getStatsGlobales()
    {
        $query = "SELECT 
                  COUNT(*) as total_presences,
                  SUM(CASE WHEN statut = 'present' THEN 1 ELSE 0 END) as total_presents,
                  SUM(CASE WHEN statut = 'absent' THEN 1 ELSE 0 END) as total_absents,
                  ROUND(
                      (SUM(CASE WHEN statut = 'present' THEN 1 ELSE 0 END) * 100.0) / 
                      NULLIF(COUNT(*), 0), 
                      2
                  ) as taux_presence_global,
                  SUM(CASE WHEN methode_validation = 'image' THEN 1 ELSE 0 END) as validations_automatiques,
                  SUM(CASE WHEN methode_validation = 'manuel' THEN 1 ELSE 0 END) as validations_manuelles,
                  COUNT(DISTINCT id_etudiant) as etudiants_actifs,
                  COUNT(DISTINCT id_seance) as seances_avec_presences
                  FROM " . $this->table;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Statistiques par cours (admin)
     */
    public function getStatsParCours()
    {
        $query = "SELECT 
                  c.id_cours,
                  c.nom_cours,
                  c.code_cours,
                  COUNT(DISTINCT s.id_seance) as nb_seances,
                  COUNT(DISTINCT p.id_etudiant) as nb_etudiants_uniques,
                  COUNT(*) as total_presences_possibles,
                  SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as nb_presents,
                  SUM(CASE WHEN p.statut = 'absent' THEN 1 ELSE 0 END) as nb_absents,
                  ROUND(
                      (SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) * 100.0) / 
                      NULLIF(COUNT(*), 0), 
                      2
                  ) as taux_presence
                  FROM cours c
                  JOIN seances s ON c.id_cours = s.id_cours AND s.statut = 'termine'
                  LEFT JOIN " . $this->table . " p ON s.id_seance = p.id_seance
                  GROUP BY c.id_cours
                  ORDER BY taux_presence ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Statistiques par enseignant (admin)
     */
    public function getStatsParEnseignant()
    {
        $query = "SELECT 
                  ens.id_enseignant,
                  CONCAT(u.prenom, ' ', u.nom) as nom_enseignant,
                  u.email,
                  COUNT(DISTINCT c.id_cours) as nb_cours,
                  COUNT(DISTINCT s.id_seance) as nb_seances,
                  COUNT(*) as total_presences_possibles,
                  SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as nb_presents,
                  ROUND(
                      (SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) * 100.0) / 
                      NULLIF(COUNT(*), 0), 
                      2
                  ) as taux_presence
                  FROM enseignants ens
                  JOIN utilisateurs u ON ens.id_utilisateur = u.id_utilisateur
                  LEFT JOIN cours c ON ens.id_enseignant = c.id_enseignant
                  LEFT JOIN seances s ON c.id_cours = s.id_cours AND s.statut = 'termine'
                  LEFT JOIN " . $this->table . " p ON s.id_seance = p.id_seance
                  GROUP BY ens.id_enseignant
                  ORDER BY taux_presence DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alertes : Étudiants avec taux d'absence élevé (admin)
     */
    public function getAlerteAbsences($seuilPourcentage = 30)
    {
        $query = "SELECT 
                  e.id_etudiant,
                  e.numero_etudiant,
                  u.nom, u.prenom, u.email,
                  e.niveau, e.specialite,
                  COUNT(*) as total_seances,
                  SUM(CASE WHEN p.statut = 'absent' THEN 1 ELSE 0 END) as nb_absences,
                  ROUND(
                      (SUM(CASE WHEN p.statut = 'absent' THEN 1 ELSE 0 END) * 100.0) / 
                      NULLIF(COUNT(*), 0), 
                      2
                  ) as taux_absence
                  FROM etudiants e
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  JOIN " . $this->table . " p ON e.id_etudiant = p.id_etudiant
                  GROUP BY e.id_etudiant
                  HAVING taux_absence >= :seuil
                  ORDER BY taux_absence DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seuil', $seuilPourcentage);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Évolution de la présence sur une période (pour graphiques)
     */
    public function getEvolutionPresence($dateDebut, $dateFin)
    {
        $query = "SELECT 
                  DATE(s.date_seance) as date,
                  COUNT(*) as total_presences,
                  SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as nb_presents,
                  ROUND(
                      (SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) * 100.0) / 
                      NULLIF(COUNT(*), 0), 
                      2
                  ) as taux_presence
                  FROM " . $this->table . " p
                  JOIN seances s ON p.id_seance = s.id_seance
                  WHERE s.date_seance BETWEEN :date_debut AND :date_fin
                  GROUP BY DATE(s.date_seance)
                  ORDER BY date";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date_debut', $dateDebut);
        $stmt->bindParam(':date_fin', $dateFin);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Top étudiants les plus assidus (admin)
     */
    public function getTopEtudiantsAssidus($limit = 10)
    {
        $query = "SELECT 
                  e.id_etudiant,
                  e.numero_etudiant,
                  u.nom, u.prenom,
                  e.niveau, e.specialite,
                  COUNT(*) as total_seances,
                  SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as nb_presences,
                  ROUND(
                      (SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) * 100.0) / 
                      NULLIF(COUNT(*), 0), 
                      2
                  ) as taux_presence
                  FROM etudiants e
                  JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                  JOIN " . $this->table . " p ON e.id_etudiant = p.id_etudiant
                  GROUP BY e.id_etudiant
                  HAVING COUNT(*) >= 5
                  ORDER BY taux_presence DESC, nb_presences DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
