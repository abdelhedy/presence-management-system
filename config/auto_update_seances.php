<?php

/**
 * Fichier d'initialisation à inclure au début de chaque page
 * Met à jour automatiquement les statuts des séances
 */

// Éviter les appels multiples
if (!defined('AUTO_UPDATE_SEANCES_DONE')) {
    define('AUTO_UPDATE_SEANCES_DONE', true);

    // Mettre à jour les statuts uniquement si > 1 minute depuis la dernière mise à jour
    $lastUpdate = $_SESSION['last_seances_update'] ?? 0;
    $now = time();

    if (($now - $lastUpdate) > 60) { // 1 minute
        require_once __DIR__ . '/../dao/SeanceDAO.php';

        try {
            $seanceDAO = new SeanceDAO();
            $seanceDAO->updateStatutsAutomatique();
            $seanceDAO->marquerAbsentsAutomatique();

            $_SESSION['last_seances_update'] = $now;
        } catch (Exception $e) {
            // Erreur silencieuse pour ne pas bloquer l'application
            error_log("Erreur auto-update séances: " . $e->getMessage());
        }
    }
}
