<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ce script doit être exécuté en ligne de commande.');
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

// Vérifier les séances sans statut
$stmt = $pdo->query("SELECT * FROM seances WHERE statut IS NULL OR statut = ''");
$seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Séances sans statut: " . count($seances) . PHP_EOL;
foreach ($seances as $s) {
    $statut = $s['statut'] ?? 'NULL';
    if ($statut === '') $statut = 'EMPTY';
    echo "ID:{$s['id_seance']} Date:{$s['date_seance']} {$s['heure_debut']}-{$s['heure_fin']} Statut:[$statut]" . PHP_EOL;
}

// Vérifier toutes les séances
echo PHP_EOL . "Toutes les séances:" . PHP_EOL;
$stmt = $pdo->query("SELECT id_seance, date_seance, heure_debut, heure_fin, statut FROM seances ORDER BY id_seance");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $s) {
    $statut = $s['statut'] ?? 'NULL';
    if ($statut === '') $statut = 'EMPTY';
    echo "ID:{$s['id_seance']} Date:{$s['date_seance']} {$s['heure_debut']}-{$s['heure_fin']} Statut:[$statut]" . PHP_EOL;
}
