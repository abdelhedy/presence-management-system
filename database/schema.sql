-- =============================================================
-- Presence Management System - Schéma de la base de données
-- MySQL 8.0 / MariaDB 10.4+
--
-- Import : mysql -u root -p < database/schema.sql
--          ou phpMyAdmin > Importer
-- =============================================================

CREATE DATABASE IF NOT EXISTS systeme_presence
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE systeme_presence;

-- -------------------------------------------------------------
-- Utilisateurs (étudiants, enseignants, administrateurs)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id_utilisateur   INT AUTO_INCREMENT PRIMARY KEY,
    nom              VARCHAR(100) NOT NULL,
    prenom           VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255) NOT NULL,
    type_utilisateur ENUM('etudiant', 'enseignant', 'administrateur') NOT NULL,
    numero_telephone VARCHAR(20) DEFAULT NULL,
    date_inscription TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS etudiants (
    id_etudiant     INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur  INT NOT NULL UNIQUE,
    numero_etudiant VARCHAR(50) NOT NULL UNIQUE,
    niveau          VARCHAR(50) NOT NULL,
    specialite      VARCHAR(100) NOT NULL,
    annee_scolaire  VARCHAR(20) NOT NULL,
    CONSTRAINT fk_etudiant_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateurs (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enseignants (
    id_enseignant  INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL UNIQUE,
    departement    VARCHAR(100) DEFAULT NULL,
    grade          VARCHAR(50) DEFAULT NULL,
    CONSTRAINT fk_enseignant_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateurs (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Cours et inscriptions
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cours (
    id_cours       INT AUTO_INCREMENT PRIMARY KEY,
    nom_cours      VARCHAR(150) NOT NULL,
    code_cours     VARCHAR(50) NOT NULL,
    id_enseignant  INT NOT NULL,
    description    TEXT DEFAULT NULL,
    niveau         VARCHAR(50) NOT NULL,
    specialite     VARCHAR(100) NOT NULL,
    annee_scolaire VARCHAR(20) NOT NULL,
    statut         ENUM('actif', 'archive') NOT NULL DEFAULT 'actif',
    date_creation  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cours_enseignant FOREIGN KEY (id_enseignant)
        REFERENCES enseignants (id_enseignant) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscriptions (
    id_inscription   INT AUTO_INCREMENT PRIMARY KEY,
    id_cours         INT NOT NULL,
    id_etudiant      INT NOT NULL,
    date_inscription TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut           ENUM('inscrit', 'desinscrit') NOT NULL DEFAULT 'inscrit',
    UNIQUE KEY uq_inscription (id_cours, id_etudiant),
    CONSTRAINT fk_inscription_cours FOREIGN KEY (id_cours)
        REFERENCES cours (id_cours) ON DELETE CASCADE,
    CONSTRAINT fk_inscription_etudiant FOREIGN KEY (id_etudiant)
        REFERENCES etudiants (id_etudiant) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Séances et présences
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS seances (
    id_seance   INT AUTO_INCREMENT PRIMARY KEY,
    id_cours    INT NOT NULL,
    date_seance DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin   TIME NOT NULL,
    salle       VARCHAR(50) DEFAULT NULL,
    type_seance ENUM('cours', 'TD', 'TP', 'examen') NOT NULL DEFAULT 'cours',
    -- 'terminee' (et non 'termine') : valeur utilisée par le code PHP
    statut      ENUM('planifie', 'en_cours', 'terminee', 'annule') NOT NULL DEFAULT 'planifie',
    INDEX idx_seance_date (date_seance),
    CONSTRAINT fk_seance_cours FOREIGN KEY (id_cours)
        REFERENCES cours (id_cours) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS presences (
    id_presence          INT AUTO_INCREMENT PRIMARY KEY,
    id_seance            INT NOT NULL,
    id_etudiant          INT NOT NULL,
    date_heure_marquage  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut               ENUM('present', 'absent', 'retard', 'justifie') NOT NULL,
    -- 'automatique' : absences enregistrées à la fin d'une séance
    methode_validation   ENUM('image', 'manuel', 'automatique') DEFAULT NULL,
    score_reconnaissance DECIMAL(5,2) DEFAULT NULL,
    UNIQUE KEY uq_presence (id_seance, id_etudiant),
    CONSTRAINT fk_presence_seance FOREIGN KEY (id_seance)
        REFERENCES seances (id_seance) ON DELETE CASCADE,
    CONSTRAINT fk_presence_etudiant FOREIGN KEY (id_etudiant)
        REFERENCES etudiants (id_etudiant) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Images de référence (reconnaissance faciale)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS images_reference (
    id_image        INT AUTO_INCREMENT PRIMARY KEY,
    id_etudiant     INT NOT NULL,
    chemin_image    VARCHAR(255) NOT NULL,
    encodage_facial TEXT DEFAULT NULL,
    date_ajout      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_image_etudiant (id_etudiant),
    CONSTRAINT fk_image_etudiant FOREIGN KEY (id_etudiant)
        REFERENCES etudiants (id_etudiant) ON DELETE CASCADE
) ENGINE=InnoDB;
