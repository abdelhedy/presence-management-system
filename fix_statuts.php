<?php
require_once 'config/database.php';
require_once 'dao/SeanceDAO.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== DIAGNOSTIC DES SEANCES ===" . PHP_EOL . PHP_EOL;

// 1. Vérifier les séances sans statut ou avec statut vide
$stmt = $pdo->query("SELECT id_seance, date_seance, heure_debut, heure_fin, statut FROM seances");
$seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total séances: " . count($seances) . PHP_EOL . PHP_EOL;

$seancesSansStatut = [];
foreach ($seances as $s) {
    $statutDisplay = $s['statut'];
    if ($s['statut'] === null) {
        $statutDisplay = 'NULL';
        $seancesSansStatut[] = $s;
    } elseif ($s['statut'] === '') {
        $statutDisplay = 'EMPTY';
        $seancesSansStatut[] = $s;
    }

    $dateTimeFin = $s['date_seance'] . ' ' . $s['heure_fin'];
    $estPassee = (strtotime($dateTimeFin) < time()) ? ' [PASSÉE]' : '';

    echo "ID:{$s['id_seance']} | {$s['date_seance']} {$s['heure_debut']}-{$s['heure_fin']} | Statut: [{$statutDisplay}]$estPassee" . PHP_EOL;
}

echo PHP_EOL . "Séances sans statut: " . count($seancesSansStatut) . PHP_EOL . PHP_EOL;

// 2. Fixer les séances sans statut
if (count($seancesSansStatut) > 0) {
    echo "=== CORRECTION DES STATUTS ===" . PHP_EOL . PHP_EOL;

    $dao = new SeanceDAO();

    foreach ($seancesSansStatut as $s) {
        $now = time();
        $dateTimeDebut = strtotime($s['date_seance'] . ' ' . $s['heure_debut']);
        $dateTimeFin = strtotime($s['date_seance'] . ' ' . $s['heure_fin']);

        // Déterminer le statut approprié
        if ($dateTimeFin < $now) {
            $nouveauStatut = 'terminee';
        } elseif ($dateTimeDebut <= $now && $now <= $dateTimeFin) {
            $nouveauStatut = 'en_cours';
        } else {
            $nouveauStatut = 'planifie';
        }

        // Mettre à jour
        $stmt = $pdo->prepare("UPDATE seances SET statut = ? WHERE id_seance = ?");
        $stmt->execute([$nouveauStatut, $s['id_seance']]);

        echo "✓ Séance ID:{$s['id_seance']} -> Statut mis à jour: [$nouveauStatut]" . PHP_EOL;
    }

    echo PHP_EOL . "=== MARQUAGE DES ABSENTS AUTOMATIQUES ===" . PHP_EOL . PHP_EOL;

    // Marquer les absents pour les séances terminées
    $dao->marquerAbsentsAutomatique();
    echo "✓ Absents marqués pour les séances terminées" . PHP_EOL;
}

echo PHP_EOL . "=== FIN DU DIAGNOSTIC ===" . PHP_EOL;
