<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== CRÉATION D'UN COMPTE ADMINISTRATEUR ===" . PHP_EOL . PHP_EOL;

// Données de l'admin
$email = 'admin@presence.com';
$password = 'admin123';
$nom = 'Administrateur';
$prenom = 'System';

// Hasher le mot de passe
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    // Vérifier si l'admin existe déjà
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND type_utilisateur = 'administrateur'");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        echo "⚠️  Un administrateur avec cet email existe déjà." . PHP_EOL;
        $admin = $stmt->fetch();
        echo "Email: " . $admin['email'] . PHP_EOL;
        echo "Nom: " . $admin['nom'] . " " . $admin['prenom'] . PHP_EOL;
    } else {
        // Insérer le nouvel admin
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, type_utilisateur, date_inscription) 
                               VALUES (?, ?, ?, ?, 'administrateur', NOW())");

        if ($stmt->execute([$email, $hashedPassword, $nom, $prenom])) {
            echo "✅ ADMINISTRATEUR CRÉÉ AVEC SUCCÈS !" . PHP_EOL . PHP_EOL;
            echo "📧 Email: " . $email . PHP_EOL;
            echo "🔑 Mot de passe: " . $password . PHP_EOL;
            echo "👤 Nom: " . $nom . " " . $prenom . PHP_EOL . PHP_EOL;
            echo "🌐 Connexion: http://localhost/presence-management-system/views/login.php" . PHP_EOL;
        } else {
            echo "❌ ERREUR lors de la création de l'administrateur" . PHP_EOL;
        }
    }
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . PHP_EOL;
}
