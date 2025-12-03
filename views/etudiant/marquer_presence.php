<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SeanceController.php';
require_once '../../controllers/ImageController.php';
require_once '../../config/auto_update_seances.php'; // Mise à jour automatique des statuts

AuthController::requireUserType('etudiant');

$seanceController = new SeanceController();
$imageController = new ImageController();

$idEtudiant = $_SESSION['etudiant_id'];

// Vérifier si l'étudiant a une photo via le controller
$imageResult = $imageController->getImageEtudiant($idEtudiant);
$hasImage = $imageResult['success'];

// Récupérer les séances actives du jour via le controller
$seancesResult = $seanceController->getTodayActiveByEtudiant($idEtudiant);
$seancesActives = $seancesResult['success'] ? $seancesResult['seances'] : [];

// Si ID séance passé en paramètre
$seanceSelectionnee = null;
if (isset($_GET['id_seance'])) {
    $seanceSelectionnee = $_GET['id_seance'];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marquer ma Présence - Système de Présence</title>
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

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .video-container {
            position: relative;
            max-width: 640px;
            margin: 2rem auto;
            border-radius: 16px;
            overflow: hidden;
            background: #000;
        }

        #video {
            width: 100%;
            display: block;
        }

        #canvas {
            display: none;
        }

        .capture-btn {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            border: 5px solid var(--primary-color);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .capture-btn:hover {
            transform: translateX(-50%) scale(1.1);
        }

        .capture-btn:active {
            transform: translateX(-50%) scale(0.95);
        }

        .seance-select {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 2px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }

        .seance-select:hover {
            border-color: var(--primary-color);
            background: rgba(8, 145, 178, 0.05);
        }

        .seance-select.selected {
            border-color: var(--primary-color);
            background: rgba(8, 145, 178, 0.1);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid var(--warning);
            color: var(--warning);
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid var(--info);
            color: var(--info);
        }

        .loading {
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
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
            <a href="marquer_presence.php" class="nav-item active">
                <span>✓</span> Marquer Présence
            </a>
            <a href="historique.php" class="nav-item">
                <span>📜</span> Historique
            </a>
            <a href="profil.php" class="nav-item">
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
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">✓ Marquer ma Présence</h1>
            <p style="color: var(--text-light);">Utilisez la reconnaissance faciale pour confirmer votre présence</p>
        </div>

        <?php if (!$hasImage): ?>
            <div class="alert alert-warning">
                <h3 style="margin-bottom: 0.5rem;">⚠️ Photo de profil requise</h3>
                <p style="margin-bottom: 1rem;">
                    Vous devez d'abord ajouter une photo de profil pour utiliser la reconnaissance faciale.
                </p>
                <a href="profil.php" class="btn btn-primary">
                    Ajouter une photo →
                </a>
            </div>
        <?php elseif (empty($seancesActives)): ?>
            <div class="alert alert-info">
                <h3 style="margin-bottom: 0.5rem;">ℹ️ Aucune séance en cours</h3>
                <p>
                    Il n'y a actuellement aucune séance <strong>en cours</strong> à laquelle vous pouvez marquer votre présence.
                </p>
                <p style="font-size: 0.9rem; color: var(--text-light); margin-top: 0.5rem;">
                    💡 Rappel : Vous ne pouvez marquer votre présence que pendant les horaires de la séance.
                    <a href="mes_seances.php" style="color: var(--primary-color);">Voir mon emploi du temps →</a>
                </p>
            </div>
        <?php else: ?>
            <!-- Sélection de la séance -->
            <div class="card" style="margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1.5rem;">🗓️ Sélectionnez la séance</h2>

                <div id="seancesList">
                    <?php foreach ($seancesActives as $seance): ?>
                        <?php
                        $isPresent = !empty($seance['presence_statut']) && $seance['presence_statut'] === 'present';
                        $isSelected = ($seanceSelectionnee == $seance['id_seance']);
                        ?>
                        <div class="seance-select <?= $isSelected ? 'selected' : '' ?> <?= $isPresent ? 'disabled' : '' ?>"
                            data-seance-id="<?= $seance['id_seance'] ?>"
                            onclick="<?= $isPresent ? '' : 'selectSeance(' . $seance['id_seance'] . ')' ?>">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h3 style="font-size: 1.1rem; margin-bottom: 0.3rem;">
                                        <?= htmlspecialchars($seance['nom_cours']) ?>
                                    </h3>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">
                                        <?= htmlspecialchars($seance['code_cours']) ?> •
                                        🕐 <?= substr($seance['heure_debut'], 0, 5) ?> - <?= substr($seance['heure_fin'], 0, 5) ?> •
                                        🏫 <?= htmlspecialchars($seance['salle']) ?>
                                    </p>
                                </div>
                                <?php if ($isPresent): ?>
                                    <span style="padding: 0.5rem 1rem; border-radius: 20px; background: rgba(16, 185, 129, 0.1); color: var(--success); font-weight: 600;">
                                        ✓ Déjà présent
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Zone de capture vidéo -->
            <div class="card" id="captureZone" style="display: none;">
                <h2 style="margin-bottom: 1rem; text-align: center;">📷 Reconnaissance Faciale</h2>
                <p style="text-align: center; color: var(--text-light); margin-bottom: 1.5rem;">
                    Positionnez votre visage dans le cadre et cliquez pour capturer
                </p>

                <div class="video-container">
                    <video id="video" autoplay playsinline></video>
                    <button class="capture-btn" id="captureBtn" onclick="captureImage()">
                        📸
                    </button>
                </div>
                <canvas id="canvas"></canvas>

                <div id="statusMessage" style="text-align: center; margin-top: 1.5rem; font-size: 1.1rem; font-weight: 600;"></div>

                <div id="loadingDiv" class="loading" style="display: none;">
                    <div class="spinner"></div>
                    <p style="margin-top: 1rem; color: var(--text-light);">Vérification en cours...</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let selectedSeanceId = <?= $seanceSelectionnee ?? 'null' ?>;
        let video = document.getElementById('video');
        let canvas = document.getElementById('canvas');
        let captureZone = document.getElementById('captureZone');
        let stream = null;

        function selectSeance(seanceId) {
            selectedSeanceId = seanceId;

            // Mettre à jour la sélection visuelle
            document.querySelectorAll('.seance-select').forEach(el => {
                el.classList.remove('selected');
            });
            document.querySelector(`.seance-select[data-seance-id="${seanceId}"]`).classList.add('selected');

            // Afficher la zone de capture
            captureZone.style.display = 'block';

            // Scroller vers la zone de capture
            captureZone.scrollIntoView({
                behavior: 'smooth'
            });

            // Démarrer la caméra
            startCamera();
        }

        async function startCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: {
                            ideal: 640
                        },
                        height: {
                            ideal: 480
                        },
                        facingMode: 'user'
                    }
                });
                video.srcObject = stream;
            } catch (err) {
                alert('Erreur d\'accès à la caméra: ' + err.message);
                console.error(err);
            }
        }

        function captureImage() {
            if (!selectedSeanceId) {
                alert('Veuillez sélectionner une séance');
                return;
            }

            // Capturer l'image
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            // Convertir en blob
            canvas.toBlob(async (blob) => {
                // Afficher le loading
                document.getElementById('loadingDiv').style.display = 'block';
                document.getElementById('captureBtn').style.display = 'none';

                // Créer FormData
                const formData = new FormData();
                formData.append('image', blob, 'capture.jpg');
                formData.append('id_seance', selectedSeanceId);
                formData.append('id_etudiant', <?= $idEtudiant ?>);

                try {
                    // Envoyer au serveur
                    const response = await fetch('../../api/marquer_presence.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    document.getElementById('loadingDiv').style.display = 'none';

                    if (result.success) {
                        showSuccess(result.message, result.score);

                        // Arrêter la caméra
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                        }

                        // Rediriger après 3 secondes
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 3000);
                    } else {
                        showError(result.error || 'Erreur lors de la reconnaissance faciale');
                        document.getElementById('captureBtn').style.display = 'flex';
                    }
                } catch (error) {
                    document.getElementById('loadingDiv').style.display = 'none';
                    document.getElementById('captureBtn').style.display = 'flex';
                    showError('Erreur de connexion: ' + error.message);
                }
            }, 'image/jpeg', 0.95);
        }

        function showSuccess(message, score) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.innerHTML = `
                <div style="color: var(--success); padding: 1.5rem; background: rgba(16, 185, 129, 0.1); border-radius: 12px;">
                    <p style="font-size: 3rem; margin-bottom: 0.5rem;">✓</p>
                    <p style="font-size: 1.2rem; margin-bottom: 0.5rem;">${message}</p>
                    ${score ? `<p style="font-size: 0.9rem; color: var(--text-light);">Score de confiance: ${score}%</p>` : ''}
                    <p style="font-size: 0.9rem; color: var(--text-light); margin-top: 0.5rem;">Redirection...</p>
                </div>
            `;
        }

        function showError(message) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.innerHTML = `
                <div style="color: var(--danger); padding: 1.5rem; background: rgba(239, 68, 68, 0.1); border-radius: 12px;">
                    <p style="font-size: 3rem; margin-bottom: 0.5rem;">✗</p>
                    <p>${message}</p>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem;">Veuillez réessayer</p>
                </div>
            `;
        }

        // Auto-start si séance sélectionnée
        <?php if ($seanceSelectionnee && !empty($seancesActives)): ?>
            window.addEventListener('load', () => {
                const seance = document.querySelector(`.seance-select[data-seance-id="<?= $seanceSelectionnee ?>"]`);
                if (seance && !seance.classList.contains('disabled')) {
                    selectSeance(<?= $seanceSelectionnee ?>);
                }
            });
        <?php endif; ?>

        // Nettoyer la caméra lors de la fermeture
        window.addEventListener('beforeunload', () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });
    </script>
</body>

</html>