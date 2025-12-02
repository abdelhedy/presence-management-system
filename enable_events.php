<?php
require_once 'config/Database.php';

$database = new Database();
$conn = $database->getConnection();

echo "=== RÉACTIVATION DES ÉVÉNEMENTS ===\n\n";

// Réactiver les événements
echo "Réactivation des événements...\n";
$conn->exec("ALTER EVENT ev_set_seance_en_cours ENABLE");
echo "   ✓ ev_set_seance_en_cours activé\n";

$conn->exec("ALTER EVENT ev_set_seance_terminee ENABLE");
echo "   ✓ ev_set_seance_terminee activé\n";

$conn->exec("ALTER EVENT ev_insert_absents ENABLE");
echo "   ✓ ev_insert_absents activé\n";

// Vérifier le statut
echo "\nÉtat actuel des événements:\n";
$stmt = $conn->query("SHOW EVENTS FROM systeme_presence");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($events as $event) {
    echo "   - {$event['Name']}: {$event['Status']}\n";
}

echo "\n✅ Tous les événements sont maintenant actifs !\n";
echo "\nIls s'exécuteront automatiquement chaque minute pour:\n";
echo "   1. Passer les séances en 'en_cours' quand l'heure de début est atteinte\n";
echo "   2. Passer les séances en 'termine' quand l'heure de fin est atteinte\n";
echo "   3. Insérer les absences automatiques pour les étudiants qui n'ont pas marqué leur présence\n";
