<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ce script doit être exécuté en ligne de commande.');
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== CRÉATION D'UN COMPTE ADMINISTRATEUR ===" . PHP_EOL . PHP_EOL;

// Données de l'admin (définies dans .env)
$email = env('ADMIN_EMAIL', 'admin@presence.com');
$password = env('ADMIN_PASSWORD', '');
$nom = 'Administrateur';
$prenom = 'System';

if (strlen($password) < 8) {
    echo "❌ Définissez ADMIN_PASSWORD (8 caractères minimum) dans le fichier .env" . PHP_EOL;
    exit(1);
}

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
            echo "🔑 Mot de passe: celui défini dans ADMIN_PASSWORD" . PHP_EOL;
            echo "👤 Nom: " . $nom . " " . $prenom . PHP_EOL . PHP_EOL;
            echo "🌐 Connexion: " . env('APP_URL', 'http://localhost/presence-management-system') . "/views/login.php" . PHP_EOL;
        } else {
            echo "❌ ERREUR lors de la création de l'administrateur" . PHP_EOL;
        }
    }
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . PHP_EOL;
}
