<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/CoursController.php';
require_once '../../controllers/SeanceController.php';
require_once '../../config/auto_update_seances.php'; // Mise à jour automatique des statuts

AuthController::requireUserType('enseignant');

$coursController = new CoursController();
$seanceController = new SeanceController();

$idEnseignant = $_SESSION['enseignant_id'];

// Récupérer les cours de l'enseignant via le controller
$coursResult = $coursController->getMesCours($idEnseignant);
$mesCours = $coursResult['success'] ? $coursResult['cours'] : [];

// Récupérer les séances à venir via le controller
$seancesResult = $seanceController->getUpcomingByEnseignant($idEnseignant, 5);
$seancesAvenir = $seancesResult['success'] ? $seancesResult['seances'] : [];

// Statistiques via le controller
$statsResult = $seanceController->getStatsEnseignant($idEnseignant);
$stats = $statsResult['success'] ? $statsResult['stats'] : [];

$nbCours = count($mesCours);
$nbSeancesEnCours = 0; // Sera calculé par le controller si nécessaire
$nbSeancesToday = 0;
foreach ($seancesAvenir as $seance) {
    if ($seance['date_seance'] === date('Y-m-d')) {
        $nbSeancesToday++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Enseignant - Système de Présence</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
            background: rgba(8, 145, 178, 0.05);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .cours-item {
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 8px;
            margin-bottom: 0.8rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            <a href="dashboard.php" class="nav-item active">
                <span>📊</span> Dashboard
            </a>
            <a href="mes_cours.php" class="nav-item">
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
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Bienvenue, <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</h1>
                <p style="color: var(--text-light);"><?php
                                                        $jours = ['Sunday' => 'Dimanche', 'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi'];
                                                        $mois = ['January' => 'janvier', 'February' => 'février', 'March' => 'mars', 'April' => 'avril', 'May' => 'mai', 'June' => 'juin', 'July' => 'juillet', 'August' => 'août', 'September' => 'septembre', 'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre'];
                                                        $date = date('l d F Y');
                                                        foreach ($jours as $en => $fr) {
                                                            $date = str_replace($en, $fr, $date);
                                                        }
                                                        foreach ($mois as $en => $fr) {
                                                            $date = str_replace($en, $fr, $date);
                                                        }
                                                        echo $date;
                                                        ?></p>
            </div>
            <a href="create_cours.php" class="btn btn-primary">
                ➕ Créer un cours
            </a>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(8, 145, 178, 0.1); color: var(--primary-color);">
                    📚
                </div>
                <div>
                    <h3 style="font-size: 2rem; margin-bottom: 0.2rem;"><?= $nbCours ?></h3>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Cours actifs</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                    🗓️
                </div>
                <div>
                    <h3 style="font-size: 2rem; margin-bottom: 0.2rem;"><?= $nbSeancesToday ?></h3>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Séances aujourd'hui</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    ⏳
                </div>
                <div>
                    <h3 style="font-size: 2rem; margin-bottom: 0.2rem;"><?= $nbSeancesEnCours ?></h3>
                    <p style="color: var(--text-light); font-size: 0.9rem;">En cours maintenant</p>
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
            <!-- Séances à venir -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🗓️</span> Séances à venir
                </h2>

                <?php if (empty($seancesAvenir)): ?>
                    <div style="text-align: center; padding: 3rem 0; color: var(--text-light);">
                        <p style="font-size: 3rem; margin-bottom: 1rem;">📅</p>
                        <p>Aucune séance planifiée</p>
                        <a href="planifier_seance.php" class="btn btn-primary" style="margin-top: 1rem;">
                            Planifier une séance
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($seancesAvenir as $seance): ?>
                        <div class="seance-item">
                            <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 0.5rem;">
                                <div style="flex: 1;">
                                    <h3 style="font-size: 1.1rem; margin-bottom: 0.3rem;">
                                        <?= htmlspecialchars($seance['nom_cours']) ?>
                                    </h3>
                                    <p style="color: var(--text-light); font-size: 0.9rem;">
                                        <?= htmlspecialchars($seance['code_cours']) ?>
                                    </p>
                                </div>
                                <?php
                                $badgeClass = 'badge-info';
                                $badgeText = 'Planifiée';
                                if ($seance['statut'] === 'en_cours') {
                                    $badgeClass = 'badge-success';
                                    $badgeText = 'En cours';
                                } elseif ($seance['statut'] === 'terminee') {
                                    $badgeClass = 'badge-warning';
                                    $badgeText = 'Terminée';
                                } elseif ($seance['statut'] === 'annule') {
                                    $badgeClass = 'badge-danger';
                                    $badgeText = 'Annulée';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                            </div>
                            <div style="display: flex; gap: 1.5rem; font-size: 0.9rem; color: var(--text-light);">
                                <span>📅 <?= date('d/m/Y', strtotime($seance['date_seance'])) ?></span>
                                <span>🕐 <?= substr($seance['heure_debut'], 0, 5) ?> - <?= substr($seance['heure_fin'], 0, 5) ?></span>
                                <span>🏫 <?= htmlspecialchars($seance['salle']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <a href="mes_seances.php" class="btn btn-outline" style="width: 100%; margin-top: 1rem;">
                        Voir toutes les séances →
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mes cours -->
            <div class="card">
                <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📚</span> Mes Cours
                </h2>

                <?php if (empty($mesCours)): ?>
                    <div style="text-align: center; padding: 2rem 0; color: var(--text-light);">
                        <p style="font-size: 2.5rem; margin-bottom: 1rem;">📚</p>
                        <p>Aucun cours créé</p>
                        <a href="create_cours.php" class="btn btn-primary" style="margin-top: 1rem; font-size: 0.9rem;">
                            Créer un cours
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($mesCours, 0, 5) as $cours): ?>
                        <div class="cours-item">
                            <div>
                                <h4 style="margin-bottom: 0.2rem; font-size: 0.95rem;">
                                    <?= htmlspecialchars($cours['nom_cours']) ?>
                                </h4>
                                <p style="color: var(--text-light); font-size: 0.85rem;">
                                    <?= htmlspecialchars($cours['code_cours']) ?> • <?= $cours['nb_etudiants'] ?? 0 ?> étudiants
                                </p>
                            </div>
                            <a href="cours_detail.php?id=<?= $cours['id_cours'] ?>" class="btn btn-sm" style="font-size: 0.85rem;">
                                Voir
                            </a>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($mesCours) > 5): ?>
                        <a href="mes_cours.php" class="btn btn-outline" style="width: 100%; margin-top: 1rem; font-size: 0.9rem;">
                            Voir tous les cours (<?= count($mesCours) ?>)
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="card" style="margin-top: 2rem;">
            <h2 style="margin-bottom: 1.5rem;">⚡ Actions rapides</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="create_cours.php" class="btn btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    ➕ Créer un cours
                </a>
                <a href="planifier_seance.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    📅 Planifier une séance
                </a>
                <a href="presences.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    ✓ Voir les présences
                </a>
                <a href="statistiques.php" class="btn btn-outline" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    📈 Statistiques
                </a>
            </div>
        </div>
    </div>
</body>

</html>