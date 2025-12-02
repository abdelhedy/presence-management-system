<?php
require_once 'config/Database.php';

$database = new Database();
$conn = $database->getConnection();

echo "=== CORRECTION DES ABSENCES ===\n\n";

// 1. Modifier l'enum pour ajouter 'automatique'
echo "1. Modification de la colonne methode_validation...\n";
try {
    $conn->exec("ALTER TABLE presences 
                 MODIFY COLUMN methode_validation ENUM('image', 'manuel', 'automatique') DEFAULT NULL");
    echo "   ✓ Colonne modifiée avec succès\n";
} catch (PDOException $e) {
    echo "   ⚠ Erreur: " . $e->getMessage() . "\n";
}

// 2. Mettre à jour les absences sans methode_validation
echo "\n2. Mise à jour des absences sans méthode de validation...\n";
$stmt = $conn->prepare("UPDATE presences 
                        SET methode_validation = 'automatique'
                        WHERE statut = 'absent' 
                        AND methode_validation IS NULL");
$stmt->execute();
$nb_updated = $stmt->rowCount();
echo "   ✓ {$nb_updated} absence(s) mise(s) à jour\n";

// 3. Vérifier les résultats
echo "\n3. Vérification des présences:\n";
$stmt = $conn->query("SELECT statut, methode_validation, COUNT(*) as nb
                      FROM presences
                      GROUP BY statut, methode_validation
                      ORDER BY statut, methode_validation");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($results as $r) {
    $methode = $r['methode_validation'] ?? 'NULL';
    echo "   {$r['statut']} - {$methode}: {$r['nb']}\n";
}

echo "\n=== TERMINÉ ===\n";
