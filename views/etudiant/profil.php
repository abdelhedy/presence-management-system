<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/ImageController.php';
require_once '../../controllers/EtudiantController.php';

AuthController::requireUserType('etudiant');

$imageController = new ImageController();
$etudiantController = new EtudiantController();

$idEtudiant = $_SESSION['etudiant_id'];

// Récupérer l'image actuelle via le controller
$imageResult = $imageController->getImageEtudiant($idEtudiant);
$currentImage = $imageResult['success'] ? $imageResult['image'] : null;

// Traitement de l'upload
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    // Debug
    error_log("=== DEBUG UPLOAD ===");
    error_log("ID Etudiant: " . $idEtudiant);
    error_log("Fichier: " . print_r($_FILES['photo'], true));

    $result = $imageController->uploadPhoto($idEtudiant, $_FILES['photo']);

    error_log("Résultat upload: " . print_r($result, true));

    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
        // Recharger l'image via le controller
        $imageResult = $imageController->getImageEtudiant($idEtudiant);
        $currentImage = $imageResult['success'] ? $imageResult['image'] : null;
    } else {
        $message = $result['error'];
        $messageType = 'error';
    }
}

// Récupérer les informations de l'étudiant via le controller
$etudiantResult = $etudiantController->getById($idEtudiant);
$etudiant = $etudiantResult['success'] ? $etudiantResult['etudiant'] : null;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Système de Présence</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(135deg, var(--dark-bg), #1e293b);
            color: white;
            padding: 2rem 0;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-item {
            padding: 0.9rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(8, 145, 178, 0.2);
            color: white;
            border-left: 4px solid var(--primary-color);
        }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            background: var(--light-bg);
            min-height: 100vh;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .profile-photo {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 16px;
            object-fit: cover;
            background: var(--light-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8rem;
            color: var(--text-light);
        }

        .upload-area {
            border: 3px dashed var(--border-color);
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }

        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(8, 145, 178, 0.05);
        }

        .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(8, 145, 178, 0.1);
        }

        .info-row {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-label {
            font-weight: 600;
            color: var(--text-light);
            width: 150px;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid var(--success);
            color: var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid var(--danger);
            color: var(--danger);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2 style="font-size: 1.3rem;">🎓 Étudiant</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
        </div>

        <nav>
            <a href="dashboard.php" class="nav-item">
                <span>📊</span> Dashboard
            </a>
            <a href="mes_cours.php" class="nav-item">
                <span>📚</span> Mes Cours
            </a>
            <a href="mes_seances.php" class="nav-item">
                <span>🗓️</span> Mes Séances
            </a>
            <a href="marquer_presence.php" class="nav-item">
                <span>✓</span> Marquer Présence
            </a>
            <a href="historique.php" class="nav-item">
                <span>📜</span> Historique
            </a>
            <a href="profil.php" class="nav-item active">
                <span>👤</span> Mon Profil
            </a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem;">
                <span>🚪</span> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">👤 Mon Profil</h1>
            <p style="color: var(--text-light);">Gérez votre photo de profil et vos informations</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Photo de profil -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">📸 Photo de profil</h2>

                <?php if ($currentImage): ?>
                    <img src="../../<?= htmlspecialchars($currentImage['chemin_image']) ?>"
                        alt="Photo de profil"
                        class="profile-photo">
                    <p style="text-align: center; color: var(--text-light); font-size: 0.9rem; margin-top: 1rem;">
                        Ajoutée le <?= date('d/m/Y à H:i', strtotime($currentImage['date_ajout'])) ?>
                    </p>
                <?php else: ?>
                    <div class="profile-photo">
                        👤
                    </div>
                    <p style="text-align: center; color: var(--text-light); margin-top: 1rem;">
                        Aucune photo de profil
                    </p>
                <?php endif; ?>

                <!-- Zone d'upload -->
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-area" onclick="document.getElementById('photoInput').click();">
                        <p style="font-size: 3rem; margin-bottom: 0.5rem;">📷</p>
                        <h3 style="margin-bottom: 0.5rem;">
                            <?= $currentImage ? 'Mettre à jour la photo' : 'Ajouter une photo' ?>
                        </h3>
                        <p style="color: var(--text-light); font-size: 0.9rem;">
                            Cliquez ou glissez une image ici
                        </p>
                        <p style="color: var(--text-light); font-size: 0.8rem; margin-top: 0.5rem;">
                            JPG, JPEG ou PNG - Maximum 5MB
                        </p>
                    </div>
                    <input type="file"
                        id="photoInput"
                        name="photo"
                        accept="image/jpeg,image/jpg,image/png"
                        style="display: none;"
                        onchange="previewAndSubmit(this)">
                </form>

                <div style="background: rgba(8, 145, 178, 0.1); border-radius: 12px; padding: 1rem; margin-top: 1.5rem;">
                    <h4 style="margin-bottom: 0.5rem; color: var(--primary-color);">💡 Conseils</h4>
                    <ul style="color: var(--text-light); font-size: 0.9rem; line-height: 1.8; padding-left: 1.5rem;">
                        <li>Prenez la photo de face</li>
                        <li>Assurez un bon éclairage</li>
                        <li>Évitez les lunettes de soleil</li>
                        <li>Restez seul sur la photo</li>
                    </ul>
                </div>
            </div>

            <!-- Informations personnelles -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem;">ℹ️ Informations personnelles</h2>

                <div class="info-row">
                    <div class="info-label">Nom complet</div>
                    <div><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                </div>

                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div><?= htmlspecialchars($_SESSION['user_email']) ?></div>
                </div>

                <?php if ($etudiant): ?>
                    <div class="info-row">
                        <div class="info-label">Numéro étudiant</div>
                        <div><?= htmlspecialchars($etudiant->numero_etudiant) ?></div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Niveau</div>
                        <div><?= htmlspecialchars($etudiant->niveau) ?></div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Spécialité</div>
                        <div><?= htmlspecialchars($etudiant->specialite) ?></div>
                    </div>

                    <div class="info-row" style="border-bottom: none;">
                        <div class="info-label">Année scolaire</div>
                        <div><?= htmlspecialchars($etudiant->annee_scolaire) ?></div>
                    </div>
                <?php endif; ?>

                <div style="background: var(--light-bg); border-radius: 12px; padding: 1.5rem; margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;">🔒 Sécurité</h3>
                    <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 1rem;">
                        Votre photo de profil est utilisée pour la reconnaissance faciale lors du marquage de présence.
                    </p>
                    <p style="color: var(--text-light); font-size: 0.9rem;">
                        Les données sont stockées de manière sécurisée et ne sont utilisées que dans le cadre du système de présence.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Drag and drop
        const uploadArea = document.querySelector('.upload-area');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => {
                uploadArea.classList.remove('dragover');
            }, false);
        });

        uploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('photoInput').files = files;
            previewAndSubmit(document.getElementById('photoInput'));
        });

        function previewAndSubmit(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Vérifier le type
                if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
                    alert('Veuillez sélectionner une image JPG, JPEG ou PNG');
                    return;
                }

                // Vérifier la taille
                if (file.size > 5 * 1024 * 1024) {
                    alert('La taille de l\'image ne doit pas dépasser 5MB');
                    return;
                }

                // Soumettre le formulaire
                if (confirm('Voulez-vous uploader cette photo de profil ?')) {
                    document.getElementById('uploadForm').submit();
                }
            }
        }
    </script>
</body>

</html>