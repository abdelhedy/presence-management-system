<?php
require_once 'config/Database.php';

$database = new Database();
$conn = $database->getConnection();

echo "=== TEST DES ÉVÉNEMENTS ===\n\n";

// 1. Vérifier les événements
echo "1. Événements existants:\n";
$stmt = $conn->query("SHOW EVENTS FROM systeme_presence");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($events as $event) {
    echo "   - {$event['Name']}: {$event['Status']}\n";
}

// 2. Créer l'événement ev_insert_absents s'il n'existe pas
echo "\n2. Création de l'événement ev_insert_absents...\n";
try {
    $conn->exec("CREATE EVENT IF NOT EXISTS ev_insert_absents
                 ON SCHEDULE EVERY 1 MINUTE
                 DO
                     INSERT INTO presences (id_seance, id_etudiant, statut, methode_validation, date_heure_marquage)
                     SELECT s.id_seance,
                            i.id_etudiant,
                            'absent',
                            'automatique',
                            NOW()
                     FROM seances s
                     JOIN inscriptions i ON i.id_cours = s.id_cours
                     WHERE s.statut = 'termine'
                       AND i.statut = 'inscrit'
                       AND NOT EXISTS (
                           SELECT 1 FROM presences p
                           WHERE p.id_seance = s.id_seance
                             AND p.id_etudiant = i.id_etudiant
                       )");
    echo "   ✓ Événement créé\n";
} catch (PDOException $e) {
    echo "   ⚠ Événement existe déjà ou erreur: " . $e->getMessage() . "\n";
}

// 3. Désactiver temporairement les événements
echo "\n3. Désactivation des événements...\n";
$conn->exec("ALTER EVENT ev_set_seance_en_cours DISABLE");
$conn->exec("ALTER EVENT ev_set_seance_terminee DISABLE");
$conn->exec("ALTER EVENT ev_insert_absents DISABLE");
echo "   ✓ Événements désactivés\n";

// 4. Vérifier les séances
echo "\n4. Séances actuelles:\n";
$stmt = $conn->query("SELECT id_seance, date_seance, heure_debut, heure_fin, statut, 
                             CONCAT(date_seance, ' ', heure_debut) as debut_complet,
                             CONCAT(date_seance, ' ', heure_fin) as fin_complet,
                             NOW() as maintenant
                      FROM seances 
                      ORDER BY date_seance DESC, heure_debut DESC 
                      LIMIT 5");
$seances = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($seances as $s) {
    echo "   Séance #{$s['id_seance']}: {$s['date_seance']} {$s['heure_debut']}-{$s['heure_fin']} => Statut: {$s['statut']}\n";
    echo "      Maintenant: {$s['maintenant']}\n";
    echo "      Début: {$s['debut_complet']} | Fin: {$s['fin_complet']}\n";
}

// 5. Test manuel: mettre à jour les statuts des séances
echo "\n5. Mise à jour manuelle des statuts de séances...\n";
$stmt = $conn->exec("UPDATE seances
                     SET statut = 'en_cours'
                     WHERE statut = 'planifie'
                       AND NOW() >= CONCAT(date_seance, ' ', heure_debut)");
echo "   ✓ Séances passées en 'en_cours': $stmt\n";

$stmt = $conn->exec("UPDATE seances
                     SET statut = 'termine'
                     WHERE statut = 'en_cours'
                       AND NOW() >= CONCAT(date_seance, ' ', heure_fin)");
echo "   ✓ Séances passées en 'termine': $stmt\n";

// 6. Vérifier les inscriptions et présences pour les séances terminées
echo "\n6. Séances terminées avec étudiants:\n";
$stmt = $conn->query("SELECT s.id_seance, 
                             COUNT(DISTINCT i.id_etudiant) as nb_inscrits,
                             COUNT(DISTINCT p.id_etudiant) as nb_presences
                      FROM seances s
                      JOIN inscriptions i ON i.id_cours = s.id_cours AND i.statut = 'inscrit'
                      LEFT JOIN presences p ON p.id_seance = s.id_seance AND p.id_etudiant = i.id_etudiant
                      WHERE s.statut = 'termine'
                      GROUP BY s.id_seance
                      ORDER BY s.date_seance DESC
                      LIMIT 5");
$seances_termine = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($seances_termine as $s) {
    $manquants = $s['nb_inscrits'] - $s['nb_presences'];
    echo "   Séance #{$s['id_seance']}: {$s['nb_inscrits']} inscrits, {$s['nb_presences']} présences marquées, {$manquants} manquants\n";
}

// 7. Insérer les absents manuellement
echo "\n7. Insertion des absents pour les séances terminées...\n";
$stmt = $conn->prepare("INSERT INTO presences (id_seance, id_etudiant, statut, methode_validation, date_heure_marquage)
                        SELECT s.id_seance,
                               i.id_etudiant,
                               'absent',
                               'automatique',
                               NOW()
                        FROM seances s
                        JOIN inscriptions i ON i.id_cours = s.id_cours
                        WHERE s.statut = 'termine'
                          AND i.statut = 'inscrit'
                          AND NOT EXISTS (
                              SELECT 1 FROM presences p
                              WHERE p.id_seance = s.id_seance
                                AND p.id_etudiant = i.id_etudiant
                          )");
$stmt->execute();
$nb_absents = $stmt->rowCount();
echo "   ✓ {$nb_absents} absents insérés\n";

// 8. Vérifier après insertion
echo "\n8. Vérification après insertion:\n";
$stmt = $conn->query("SELECT s.id_seance, 
                             COUNT(DISTINCT i.id_etudiant) as nb_inscrits,
                             COUNT(DISTINCT p.id_etudiant) as nb_presences,
                             SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as nb_presents,
                             SUM(CASE WHEN p.statut = 'absent' THEN 1 ELSE 0 END) as nb_absents
                      FROM seances s
                      JOIN inscriptions i ON i.id_cours = s.id_cours AND i.statut = 'inscrit'
                      LEFT JOIN presences p ON p.id_seance = s.id_seance AND p.id_etudiant = i.id_etudiant
                      WHERE s.statut = 'termine'
                      GROUP BY s.id_seance
                      ORDER BY s.date_seance DESC
                      LIMIT 5");
$seances_final = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($seances_final as $s) {
    echo "   Séance #{$s['id_seance']}:\n";
    echo "      Total: {$s['nb_inscrits']} | Présents: {$s['nb_presents']} | Absents: {$s['nb_absents']}\n";
}

echo "\n=== TEST TERMINÉ ===\n";
echo "\nPour réactiver les événements, exécutez:\n";
echo "ALTER EVENT ev_set_seance_en_cours ENABLE;\n";
echo "ALTER EVENT ev_set_seance_terminee ENABLE;\n";
echo "ALTER EVENT ev_insert_absents ENABLE;\n";
