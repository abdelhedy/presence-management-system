<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/CoursController.php';

AuthController::requireUserType('enseignant');

$coursController = new CoursController();
$idEnseignant = $_SESSION['enseignant_id'];

// Récupérer les cours de l'enseignant
$coursResult = $coursController->getMesCours($idEnseignant);
$mesCours = $coursResult['success'] ? $coursResult['cours'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours - Système de Présence</title>
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

        .sidebar-nav {
            padding: 1rem 0;
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

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .course-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .course-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .course-code {
            background: rgba(8, 145, 178, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .course-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.5rem;
        }

        .course-info {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .course-info span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .course-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-action {
            flex: 1;
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .btn-seances {
            background: rgba(8, 145, 178, 0.1);
            color: var(--primary-color);
        }

        .btn-seances:hover {
            background: rgba(8, 145, 178, 0.2);
        }

        .btn-presences {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .btn-presences:hover {
            background: rgba(16, 185, 129, 0.2);
        }

        .btn-planifier {
            background: var(--primary-color);
            color: white;
        }

        .btn-planifier:hover {
            background: #067a9c;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
        }

        .empty-state h3 {
            color: var(--dark-bg);
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.5rem;">👨‍🏫 Enseignant</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span>📊</span> Dashboard
            </a>
            <a href="mes_cours.php" class="nav-item active">
                <span>📚</span> Mes Cours
            </a>
            <a href="create_cours.php" class="nav-item">
                <span>➕</span> Créer un Cours
            </a>
            <a href="mes_seances.php" class="nav-item">
                <span>🗓️</span> Mes Séances
            </a>
            <a href="planifier_seance.php" class="nav-item">
                <span>📅</span> Planifier une Séance
            </a>
            <a href="presences.php" class="nav-item">
                <span>✓</span> Présences
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
                <p style="color: var(--text-light);">Gérez vos cours et consultez les séances</p>
            </div>
            <a href="create_cours.php" class="btn btn-primary">
                ➕ Nouveau cours
            </a>
        </div>

        <?php if (empty($mesCours)): ?>
            <div class="empty-state">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                <h3>Aucun cours pour le moment</h3>
                <p style="color: var(--text-light); margin-bottom: 2rem;">Commencez par créer votre premier cours</p>
                <a href="create_cours.php" class="btn btn-primary">➕ Créer un cours</a>
            </div>
        <?php else: ?>
            <div class="courses-grid">
                <?php foreach ($mesCours as $cours): ?>
                    <div class="course-card">
                        <div class="course-header">
                            <div>
                                <div class="course-title"><?= htmlspecialchars($cours['nom_cours']) ?></div>
                                <div class="course-code"><?= htmlspecialchars($cours['code_cours']) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($cours['description'])): ?>
                            <p style="color: var(--text-light); margin: 1rem 0; font-size: 0.9rem;">
                                <?= htmlspecialchars(substr($cours['description'], 0, 100)) ?><?= strlen($cours['description']) > 100 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>

                        <div class="course-info">
                            <span>🎓 <?= htmlspecialchars($cours['niveau']) ?></span>
                            <span>📖 <?= htmlspecialchars($cours['specialite']) ?></span>
                        </div>

                        <div class="course-info">
                            <span>👥 <?= $cours['nb_etudiants'] ?? 0 ?> étudiants</span>
                            <span>🗓️ <?= $cours['nb_seances'] ?? 0 ?> séances</span>
                        </div>

                        <div class="course-actions">
                            <a href="cours_seances.php?id=<?= $cours['id_cours'] ?>" class="btn-action btn-seances">
                                📅 Séances
                            </a>
                            <a href="presences.php?id_cours=<?= $cours['id_cours'] ?>" class="btn-action btn-presences">
                                ✓ Présences
                            </a>
                        </div>

                        <div style="margin-top: 0.5rem;">
                            <a href="planifier_seance.php?id_cours=<?= $cours['id_cours'] ?>" class="btn-action btn-planifier">
                                ➕ Planifier une séance
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>