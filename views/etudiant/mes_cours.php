<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/EtudiantController.php';

AuthController::requireUserType('etudiant');

$etudiantController = new EtudiantController();
$idEtudiant = $_SESSION['etudiant_id'] ?? null;

// Debug
if (!$idEtudiant) {
    echo '<pre>Session data: ';
    print_r($_SESSION);
    echo '</pre>';
    die('ID étudiant non trouvé dans la session. Veuillez vous reconnecter.');
}

// Récupérer les cours de l'étudiant
$coursResult = $etudiantController->getMesCours($idEtudiant);
$mesCours = $coursResult['success'] ? $coursResult['cours'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours - Étudiant</title>
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
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
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
            font-weight: 500;
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

        .cours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .cours-card {
            background: white;
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border-top: 4px solid var(--primary-color);
        }

        .cours-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .cours-header {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .cours-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .badge {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(8, 145, 178, 0.1);
            color: var(--primary-color);
        }

        .cours-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 1rem 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.5rem;">🎓 Étudiant</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
        </div>

        <nav>
            <a href="dashboard.php" class="nav-item">
                <span>📊</span> Dashboard
            </a>
            <a href="mes_cours.php" class="nav-item active">
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
            <a href="profil.php" class="nav-item">
                <span>👤</span> Mon Profil
            </a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
                <span>🚪</span> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">📚 Mes Cours</h1>
                <p style="color: var(--text-light);">Liste de tous mes cours inscrits</p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span class="badge" style="font-size: 1rem; padding: 0.6rem 1.2rem;">
                    <?= count($mesCours) ?> cours
                </span>
            </div>
        </div>

        <?php if (empty($mesCours)): ?>
            <div class="empty-state">
                <p style="font-size: 4rem; margin-bottom: 1rem;">📚</p>
                <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Aucun cours</h3>
                <p style="color: var(--text-light);">Vous n'êtes inscrit à aucun cours pour le moment.</p>
                <p style="color: var(--text-light); font-size: 0.9rem; margin-top: 1rem;">
                    Contactez votre enseignant ou l'administration pour vous inscrire.
                </p>
            </div>
        <?php else: ?>
            <div class="cours-grid">
                <?php foreach ($mesCours as $cours): ?>
                    <div class="cours-card">
                        <div class="cours-header">
                            <div class="cours-icon">📚</div>
                            <div style="flex: 1;">
                                <h3 style="font-size: 1.1rem; margin-bottom: 0.3rem;">
                                    <?= htmlspecialchars($cours['nom_cours']) ?>
                                </h3>
                                <span class="badge"><?= htmlspecialchars($cours['code_cours']) ?></span>
                            </div>
                        </div>

                        <div class="cours-info">
                            <div class="info-row">
                                <span>👨‍🏫</span>
                                <span><?= htmlspecialchars($cours['nom_enseignant']) ?></span>
                            </div>
                            <?php if (!empty($cours['description'])): ?>
                                <?php
                                $description = $cours['description'];
                                $maxLength = 80;
                                $isTruncated = strlen($description) > $maxLength;
                                if ($isTruncated) {
                                    $shortDescription = substr($description, 0, $maxLength) . '...';
                                }
                                ?>
                                <div class="info-row" style="flex-direction: column; align-items: start;">
                                    <div style="display: flex; gap: 0.5rem; width: 100%;">
                                        <span>📝</span>
                                        <span style="line-height: 1.4; flex: 1;" id="desc-<?= $cours['id_cours'] ?>">
                                            <?= htmlspecialchars($isTruncated ? $shortDescription : $description) ?>
                                        </span>
                                    </div>
                                    <?php if ($isTruncated): ?>
                                        <button onclick="toggleDescription(<?= $cours['id_cours'] ?>)"
                                            id="btn-<?= $cours['id_cours'] ?>"
                                            style="margin-left: 2rem; margin-top: 0.3rem; background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 0.85rem; text-decoration: underline;">
                                            Voir plus
                                        </button>
                                        <span style="display: none;" id="full-<?= $cours['id_cours'] ?>"><?= htmlspecialchars($description) ?></span>
                                        <span style="display: none;" id="short-<?= $cours['id_cours'] ?>"><?= htmlspecialchars($shortDescription) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; font-size: 0.85rem;">
                            <div style="text-align: center; padding: 0.8rem; background: rgba(16, 185, 129, 0.1); border-radius: 8px;">
                                <div style="font-size: 1.3rem; font-weight: 700; color: var(--success);">
                                    <?= $cours['nb_seances'] ?? 0 ?>
                                </div>
                                <div style="color: var(--text-light); margin-top: 0.2rem;">Séances</div>
                            </div>
                            <div style="text-align: center; padding: 0.8rem; background: rgba(8, 145, 178, 0.1); border-radius: 8px;">
                                <div style="font-size: 1.3rem; font-weight: 700; color: var(--primary-color);">
                                    <?= $cours['nb_inscrits'] ?? 0 ?>
                                </div>
                                <div style="color: var(--text-light); margin-top: 0.2rem;">Étudiants</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleDescription(coursId) {
            const descElement = document.getElementById('desc-' + coursId);
            const btnElement = document.getElementById('btn-' + coursId);
            const fullText = document.getElementById('full-' + coursId).textContent;
            const shortText = document.getElementById('short-' + coursId).textContent;

            if (btnElement.textContent === 'Voir plus') {
                descElement.textContent = fullText;
                btnElement.textContent = 'Voir moins';
            } else {
                descElement.textContent = shortText;
                btnElement.textContent = 'Voir plus';
            }
        }
    </script>
</body>

</html>