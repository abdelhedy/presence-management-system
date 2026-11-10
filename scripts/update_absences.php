<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ce script doit être exécuté en ligne de commande.');
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->exec("UPDATE presences SET methode_validation = 'automatique' WHERE statut = 'absent'");
echo "✓ {$stmt} absence(s) mise(s) à jour avec methode_validation = 'automatique'\n";

// Vérifier
$stmt = $conn->query("SELECT * FROM presences WHERE statut = 'absent'");
$absences = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nAbsences actuelles:\n";
foreach ($absences as $a) {
    echo "  ID: {$a['id_presence']}, Séance: {$a['id_seance']}, Étudiant: {$a['id_etudiant']}, Méthode: {$a['methode_validation']}\n";
}
