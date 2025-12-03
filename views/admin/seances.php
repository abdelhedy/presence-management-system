<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

AuthController::requireUserType('administrateur');

$adminController = new AdminController();
$seancesResult = $adminController->getAllSeances();
$seances = $seancesResult['success'] ? $seancesResult['seances'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Séances - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #fff;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto
        }

        .sidebar-header {
            padding: 0 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, .1)
        }

        .nav-item {
            padding: .9rem 1.5rem;
            color: rgba(255, 255, 255, .8);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all .3s
        }

        .nav-item.active,
        .nav-item:hover {
            background: rgba(8, 145, 178, .2);
            color: #fff;
            border-left: 4px solid var(--primary-color)
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 2rem;
            background: var(--light-bg)
        }

        .seance-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
            margin-bottom: 1rem
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>⚙️ Administration</h2>
                <p style="font-size:.9rem;color:rgba(255,255,255,.6)"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
            </div>
            <a href="dashboard.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="utilisateurs.php" class="nav-item"><span>👥</span> Utilisateurs</a>
            <a href="ajouter_utilisateur.php" class="nav-item"><span>➕</span> Ajouter Utilisateur</a>
            <a href="cours.php" class="nav-item"><span>📚</span> Cours</a>
            <a href="seances.php" class="nav-item active"><span>🗓️</span> Séances</a>
            <a href="presences.php" class="nav-item"><span>✓</span> Présences</a>
            <a href="../logout.php" class="nav-item" style="margin-top:2rem;border-top:1px solid rgba(255,255,255,.1);padding-top:1.5rem"><span>🚪</span> Déconnexion</a>
        </nav>
        <div class="main-content">
            <h1 style="font-size:2rem;margin-bottom:.5rem">🗓️ Toutes les Séances</h1>
            <p style="color:var(--text-light);margin-bottom:2rem">Vue globale de toutes les séances</p>
            <?php if (empty($seances)): ?>
                <div style="text-align:center;padding:5rem 0">
                    <p style="font-size:4rem">🗓️</p>
                    <h3>Aucune séance</h3>
                </div>
            <?php else: ?>
                <?php foreach ($seances as $s): ?>
                    <div class="seance-card">
                        <div style="display:flex;justify-content:space-between">
                            <div>
                                <h3><?= htmlspecialchars($s['nom_cours']) ?></h3>
                                <p style="color:var(--text-light);font-size:.9rem"><?= htmlspecialchars($s['nom_enseignant'] ?? '') ?></p>
                            </div>
                            <span class="badge"><?= ucfirst($s['statut']) ?></span>
                        </div>
                        <div style="margin-top:1rem;font-size:.9rem">
                            <span>📅 <?= date('d/m/Y', strtotime($s['date_seance'])) ?></span>
                            <span style="margin-left:1rem">🕐 <?= substr($s['heure_debut'], 0, 5) ?>-<?= substr($s['heure_fin'], 0, 5) ?></span>
                            <span style="margin-left:1rem">🏫 <?= htmlspecialchars($s['salle']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>