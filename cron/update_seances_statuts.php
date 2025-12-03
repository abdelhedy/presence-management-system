<?php

/**
 * Script de mise à jour automatique des statuts des séances
 * À exécuter périodiquement (toutes les 5 minutes) ou à chaque chargement de page
 */

require_once __DIR__ . '/../dao/SeanceDAO.php';

try {
    $seanceDAO = new SeanceDAO();

    // Mettre à jour les statuts des séances
    $seanceDAO->updateStatutsAutomatique();

    // Marquer les absents pour les séances terminées
    $seanceDAO->marquerAbsentsAutomatique();

    error_log("Mise à jour automatique des séances effectuée avec succès");
} catch (Exception $e) {
    error_log("Erreur lors de la mise à jour automatique: " . $e->getMessage());
}
