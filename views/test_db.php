<?php
// Test de connexion à la base de données
require_once __DIR__ . '/../config/Database.php';

echo "<h2>Test de connexion à la base de données</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✅ Connexion réussie à la base de données !</p>";
        
        // Tester si les tables existent
        $tables = ['utilisateurs', 'etudiants', 'enseignants'];
        
        echo "<h3>Vérification des tables :</h3>";
        echo "<ul>";
        
        foreach ($tables as $table) {
            $query = "SHOW TABLES LIKE '{$table}'";
            $stmt = $conn->query($query);
            
            if ($stmt->rowCount() > 0) {
                echo "<li style='color: green;'>✅ Table '{$table}' existe</li>";
            } else {
                echo "<li style='color: red;'>❌ Table '{$table}' n'existe PAS</li>";
            }
        }
        
        echo "</ul>";
        
        // Afficher la structure de la table utilisateurs
        echo "<h3>Structure de la table 'utilisateurs' :</h3>";
        $query = "DESCRIBE utilisateurs";
        $stmt = $conn->query($query);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Champ</th><th>Type</th><th>Null</th><th>Clé</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>