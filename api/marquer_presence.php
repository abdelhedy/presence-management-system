<?php
session_start();
header('Content-Type: application/json');

require_once '../controllers/PresenceController.php';
require_once '../controllers/ImageController.php';

// Vérifier l'authentification
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'etudiant') {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données
$idSeance = $_POST['id_seance'] ?? null;
$idEtudiant = $_POST['id_etudiant'] ?? null;

if (!$idSeance || !$idEtudiant) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit;
}

// Vérifier que c'est bien l'étudiant connecté
if ($idEtudiant != $_SESSION['etudiant_id']) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Vérifier qu'une image a été uploadée
if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'Image manquante']);
    exit;
}

try {
    // Sauvegarder l'image temporairement
    $uploadDir = '../uploads/temp/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $tempFilename = 'capture_' . $idEtudiant . '_' . time() . '.jpg';
    $tempPath = $uploadDir . $tempFilename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $tempPath)) {
        echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement de l\'image']);
        exit;
    }

    // Vérifier l'identité avec reconnaissance faciale
    $imageController = new ImageController();
    $verificationResult = $imageController->verifyFace($idEtudiant, $tempPath);

    // Supprimer le fichier temporaire
    unlink($tempPath);

    if (!$verificationResult['success']) {
        echo json_encode([
            'success' => false,
            'error' => $verificationResult['error'] ?? 'Erreur lors de la vérification'
        ]);
        exit;
    }

    // Vérifier le score de confiance
    $match = $verificationResult['match'] ?? false;
    $confidence = $verificationResult['confidence'] ?? 0;

    if (!$match || $confidence < 70) {
        echo json_encode([
            'success' => false,
            'error' => 'Visage non reconnu. Veuillez réessayer ou contacter votre enseignant.',
            'confidence' => $confidence
        ]);
        exit;
    }

    // Marquer la présence
    $presenceController = new PresenceController();
    $result = $presenceController->marquerPresenceReconnaissanceFaciale(
        $idSeance,
        $idEtudiant,
        $confidence
    );

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Présence marquée avec succès ! ✓',
            'score' => round($confidence, 1)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Erreur lors du marquage de la présence'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
