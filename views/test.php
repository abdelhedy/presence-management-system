<?php
session_start();
require_once '../controllers/AuthController.php';

// Test avec des données en dur
$testData = [
    'nom' => 'Test',
    'prenom' => 'Utilisateur',
    'email' => 'test@example.com',
    'password' => 'password123',
    'type_utilisateur' => 'etudiant',
    'telephone' => '12345678',
    'numero_etudiant' => 'ET2024001',
    'niveau' => '2ème année',
    'specialite' => 'Ingénierie des Données',
    'annee_scolaire' => '2024-2025'
];

$authController = new AuthController();
$result = $authController->register($testData);

echo "<pre>";
print_r($result);
echo "</pre>";