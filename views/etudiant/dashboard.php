<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SeanceController.php';
require_once '../../controllers/EtudiantController.php';
require_once '../../controllers/PresenceController.php';
require_once '../../controllers/ImageController.php';

AuthController::requireUserType('etudiant');

$seanceController = new SeanceController();
$etudiantController = new EtudiantController();
$presenceController = new PresenceController();
$imageController = new ImageController();

$idEtudiant = $_SESSION['etudiant_id'];

// Récupérer les cours de l'étudiant via le controller
$coursResult = $etudiantController->getMesCours($idEtudiant);
$mesCours = $coursResult['success'] ? $coursResult['cours'] : [];

// Récupérer les séances du jour via le controller
$seancesResult = $seanceController->getTodayActiveByEtudiant($idEtudiant);
$seancesAujourdhui = $seancesResult['success'] ? $seancesResult['seances'] : [];

// Statistiques via le controller
$statsResult = $presenceController->getStatsEtudiant($idEtudiant);
$stats = $statsResult['success'] ? $statsResult['stats'] : [];

$statsCoursResult = $presenceController->getStatsParCoursEtudiant($idEtudiant);
$statsParCours = $statsCoursResult['success'] ? $statsCoursResult['stats'] : [];

// Vérifier si l'étudiant a une photo via le controller
$imageResult = $imageController->getImageEtudiant($idEtudiant);
$hasImage = $imageResult['success'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Étudiant - Système de Présence</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .seance-item {
            padding: 1.2rem;
            border-left: 4px solid var(--primary-color);
            background: rgba(8, 145, 178, 0.05);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid var(--warning);
            color: var(--warning);
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
            <a href="dashboard.php" class="nav-item active">
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
        <?php if (!$hasImage): ?>
            <div class="alert alert-warning">
                <span style="font-size: 2rem;">⚠️</span>
                <div style="flex: 1;">
                    <strong>Photo de profil manquante !</strong>
                    <p style="margin: 0.3rem 0 0 0; font-size: 0.9rem;">
                        Vous devez ajouter votre photo de profil pour pouvoir marquer votre présence par reconnaissance faciale.
                    </p>
                </div>
                <a href="profil.php" class="btn btn-primary" style="white-space: nowrap;">
                    Ajouter une photo
                </a>
            </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Bienvenue, <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</h1>
                <p style="color: var(--text-light);"><?= strftime('%A %d %B %Y', strtotime('today')) ?></p>
            </div>
            <?php if ($hasImage): ?>
                <a href="marquer_presence.php" class="btn btn-primary">
                    ✓ Marquer ma présence
                </a>
            <?php endif; ?>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(8, 145, 178, 0.1); color: var(--primary-color);">
                    📚
                </div>
                <div>
                    <h3 style="font-size: 2rem; margin-bottom: 0.2rem;"><?= count($mesCours) ?></h3>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Cours inscrits</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                    ✓
                </div>
                <div>
                    <h3 style="font-size: 2rem; margin-bottom: 0.2rem;"><?= $stats['total_presents'] ?? 0 ?></h3>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Présences</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
                    ✗
                </div>
                <div>
                    <h3 style="font-size: 2rem; margin-bottom: 0.2rem;"><?= $stats['total_absents'] ?? 0 ?></h3>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Absences</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--info);">
                    📊
                </div>
                <div>
                    <h3 style="font-size: 2rem; margin-bottom: 0.2rem;"><?= $stats['taux_presence'] ?? 0 ?>%</h3>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Taux de présence</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Séances aujourd'hui -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🗓️</span> Mes séances aujourd'hui
                </h2>

                <?php if (empty($seancesAujourdhui)): ?>
                    <div style="text-align: center; padding: 3rem 0; color: var(--text-light);">
                        <p style="font-size: 3rem; margin-bottom: 1rem;">📅</p>
                        <p>Aucune séance aujourd'hui</p>
                        <p style="font-size: 0.9rem; margin-top: 0.5rem;">Profitez de votre journée ! 🎉</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($seancesAujourdhui as $seance): ?>
                        <?php
                        $isPresent = !empty($seance['presence_statut']) && $seance['presence_statut'] === 'present';
                        $badgeClass = $isPresent ? 'badge-success' : 'badge-warning';
                        $badgeText = $isPresent ? '✓ Présent' : 'Non marqué';
                        ?>
                        <div class="seance-item">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.8rem;">
                                <div>
                                    <h3 style="font-size: 1.1rem; margin-bottom: 0.3rem;">
                                        <?= htmlspecialchars($seance['nom_cours']) ?>
                                    </h3>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">
                                        <?= htmlspecialchars($seance['code_cours']) ?>
                                    </p>
                                </div>
                                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                            </div>
                            <div style="display: flex; gap: 1.5rem; font-size: 0.9rem; color: var(--text-light); margin-bottom: 1rem;">
                                <span>🕐 <?= substr($seance['heure_debut'], 0, 5) ?> - <?= substr($seance['heure_fin'], 0, 5) ?></span>
                                <span>🏫 <?= htmlspecialchars($seance['salle']) ?></span>
                            </div>
                            <?php if (!$isPresent && $seance['statut'] === 'en_cours'): ?>
                                <a href="marquer_presence.php?id_seance=<?= $seance['id_seance'] ?>"
                                    class="btn btn-primary" style="width: 100%; font-size: 0.9rem;">
                                    ✓ Marquer ma présence
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Statistiques par cours -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📊</span> Par cours
                </h2>

                <?php if (empty($statsParCours)): ?>
                    <div style="text-align: center; padding: 2rem 0; color: var(--text-light);">
                        <p style="font-size: 2.5rem; margin-bottom: 1rem;">📚</p>
                        <p>Aucune donnée disponible</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($statsParCours as $stat): ?>
                        <div style="margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color);">
                            <h4 style="margin-bottom: 0.5rem; font-size: 0.95rem;">
                                <?= htmlspecialchars($stat['nom_cours']) ?>
                            </h4>
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: var(--text-light); margin-bottom: 0.8rem;">
                                <span>✓ <?= $stat['nb_presents'] ?> présent(s)</span>
                                <span>✗ <?= $stat['nb_absents'] ?> absent(s)</span>
                            </div>
                            <div style="background: var(--light-bg); border-radius: 8px; height: 8px; overflow: hidden;">
                                <div style="background: var(--success); height: 100%; width: <?= $stat['taux_presence'] ?>%;"></div>
                            </div>
                            <p style="text-align: center; margin-top: 0.5rem; font-size: 0.9rem; font-weight: 600; color: var(--primary-color);">
                                <?= $stat['taux_presence'] ?>%
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="card" style="margin-top: 2rem;">
            <h2 style="margin-bottom: 1.5rem;">⚡ Actions rapides</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="marquer_presence.php" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    ✓ Marquer présence
                </a>
                <a href="mes_seances.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    🗓️ Voir mes séances
                </a>
                <a href="historique.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    📜 Mon historique
                </a>
                <a href="profil.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    👤 Mon profil
                </a>
            </div>
        </div>
    </div>
</body>

</html>