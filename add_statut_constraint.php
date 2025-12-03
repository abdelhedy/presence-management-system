<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== AJOUT DE CONTRAINTE SUR LA COLONNE STATUT ===" . PHP_EOL . PHP_EOL;

try {
    // 1. D'abord, s'assurer que toutes les séances existantes ont un statut
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM seances WHERE statut IS NULL OR statut = ''");
    $result = $stmt->fetch();

    if ($result['nb'] > 0) {
        echo "⚠️  {$result['nb']} séance(s) sans statut détectée(s)" . PHP_EOL;
        echo "→ Mise à jour en cours..." . PHP_EOL;

        // Mettre statut = 'planifie' pour celles qui n'ont rien
        $pdo->exec("UPDATE seances SET statut = 'planifie' WHERE statut IS NULL OR statut = ''");
        echo "✓ Toutes les séances ont maintenant un statut" . PHP_EOL . PHP_EOL;
    } else {
        echo "✓ Aucune séance sans statut" . PHP_EOL . PHP_EOL;
    }

    // 2. Modifier la colonne pour ajouter DEFAULT et NOT NULL
    echo "→ Modification de la structure de la table..." . PHP_EOL;

    $pdo->exec("ALTER TABLE seances 
                MODIFY COLUMN statut ENUM('planifie', 'en_cours', 'terminee', 'annule') 
                NOT NULL DEFAULT 'planifie'");

    echo "✓ Colonne 'statut' modifiée avec succès" . PHP_EOL;
    echo "  - Type: ENUM('planifie', 'en_cours', 'terminee', 'annule')" . PHP_EOL;
    echo "  - NOT NULL" . PHP_EOL;
    echo "  - DEFAULT 'planifie'" . PHP_EOL . PHP_EOL;

    echo "✅ CONTRAINTE AJOUTÉE AVEC SUCCÈS !" . PHP_EOL;
    echo PHP_EOL;
    echo "Désormais, toute nouvelle séance aura automatiquement le statut 'planifie'" . PHP_EOL;
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . PHP_EOL;
}
