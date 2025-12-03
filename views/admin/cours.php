<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

AuthController::requireUserType('administrateur');

$adminController = new AdminController();

// Récupérer tous les cours
$coursResult = $adminController->getAllCours();
$cours = $coursResult['success'] ? $coursResult['cours'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Cours - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
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
            flex: 1;
            padding: 2rem;
            background: var(--light-bg);
        }

        .cours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .cours-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .cours-card h3 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>⚙️ Administration</h2>
                <p style="font-size: 0.9rem; color: rgba(255,255,255,0.6);">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </p>
            </div>
            <a href="dashboard.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="utilisateurs.php" class="nav-item"><span>👥</span> Utilisateurs</a>
            <a href="ajouter_utilisateur.php" class="nav-item"><span>➕</span> Ajouter Utilisateur</a>
            <a href="cours.php" class="nav-item active"><span>📚</span> Cours</a>
            <a href="seances.php" class="nav-item"><span>🗓️</span> Séances</a>
            <a href="presences.php" class="nav-item"><span>✓</span> Présences</a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;"><span>🚪</span> Déconnexion</a>
        </nav>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">📚 Liste des Cours</h1>
                    <p style="color: var(--text-light);">Tous les cours créés par les enseignants</p>
                </div>
            </div>

            <?php if (empty($cours)): ?>
                <div style="text-align: center; padding: 5rem 0;">
                    <p style="font-size: 4rem; margin-bottom: 1rem;">📚</p>
                    <h3 style="margin-bottom: 0.5rem;">Aucun cours</h3>
                    <p style="color: var(--text-light);">Les enseignants doivent créer des cours depuis leur espace</p>
                </div>
            <?php else: ?>
                <div class="cours-grid">
                    <?php foreach ($cours as $c): ?>
                        <div class="cours-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <span class="badge" style="background: rgba(8,145,178,0.1); color: var(--primary-color);">
                                    <?= htmlspecialchars($c['code_cours']) ?>
                                </span>
                                <?php if (!empty($c['niveau'])): ?>
                                    <span class="badge" style="background: rgba(16,185,129,0.1); color: #10b981;">
                                        <?= htmlspecialchars($c['niveau']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3><?= htmlspecialchars($c['nom_cours']) ?></h3>

                            <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 1rem;">
                                <?= !empty($c['description']) ? htmlspecialchars(substr($c['description'], 0, 100)) . '...' : 'Aucune description' ?>
                            </p>

                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <span>👨‍🏫</span>
                                <span style="font-size: 0.9rem;">
                                    <?= htmlspecialchars($c['nom_enseignant'] ?? 'Non assigné') ?>
                                </span>
                            </div>

                            <?php if (!empty($c['nb_heures']) || !empty($c['credits'])): ?>
                                <div style="font-size: 0.9rem; color: var(--text-light); margin-top: 0.5rem;">
                                    <?php if (!empty($c['nb_heures'])): ?>
                                        <span>🕐 <?= $c['nb_heures'] ?>h</span>
                                    <?php endif; ?>
                                    <?php if (!empty($c['credits'])): ?>
                                        <span style="margin-left: 1rem;">⭐ <?= $c['credits'] ?> crédits</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div style="margin-top: 1rem;">
                                <a href="cours_seances.php?id=<?= $c['id_cours'] ?>" class="btn btn-primary" style="width: 100%; text-align: center; text-decoration: none; display: inline-block;">
                                    📅 Voir les séances
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>