<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SeanceController.php';
require_once '../../controllers/CoursController.php';
require_once '../../config/auto_update_seances.php'; // Mise à jour automatique des statuts

AuthController::requireUserType('enseignant');

$seanceController = new SeanceController();
$coursController = new CoursController();

$idEnseignant = $_SESSION['enseignant_id'];

// Récupérer les cours pour le filtre via le controller
$coursResult = $coursController->getMesCours($idEnseignant);
$mesCours = $coursResult['success'] ? $coursResult['cours'] : [];

// Filtres
$filters = [];
if (!empty($_GET['id_cours'])) {
    $filters['id_cours'] = $_GET['id_cours'];
}
if (!empty($_GET['date_debut'])) {
    $filters['date_debut'] = $_GET['date_debut'];
}
if (!empty($_GET['statut'])) {
    $filters['statut'] = $_GET['statut'];
}

// Récupérer les séances avec présences via le controller
$seancesResult = $seanceController->getByEnseignant($idEnseignant, $filters);
$seances = $seancesResult['success'] ? $seancesResult['seances'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des Présences - Système de Présence</title>
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

        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .seances-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .seance-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
        }

        .seance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .seance-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-bg);
        }

        .seance-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.5rem;
        }

        .seance-info {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .presence-stats {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }

        .stat-badge {
            padding: 0.4rem 0.7rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .stat-badge.success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .stat-badge.danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .stat-badge.info {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
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
            <h2 style="font-size: 1.3rem;">👨‍🏫 Enseignant</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
        </div>

        <nav>
            <a href="dashboard.php" class="nav-item">
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
            <a href="presences.php" class="nav-item active">
                <span>✓</span> Présences
            </a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem;">
                <span>🚪</span> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">✓ Consultation des Présences</h1>
            <p style="color: var(--text-light);">Visualisez et gérez les présences de vos séances</p>
        </div>

        <!-- Filtres -->
        <div class="filter-card">
            <h3 style="margin-bottom: 1rem;">🔍 Filtres</h3>
            <form method="GET" action="">
                <div class="filters-row">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Cours</label>
                        <select name="id_cours" class="form-control">
                            <option value="">Tous les cours</option>
                            <?php foreach ($mesCours as $cours): ?>
                                <option value="<?= $cours['id_cours'] ?>" <?= (isset($_GET['id_cours']) && $_GET['id_cours'] == $cours['id_cours']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cours['nom_cours']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">État de la séance</label>
                        <select name="statut" class="form-control">
                            <option value="">Tous les états</option>
                            <option value="terminee" <?= (isset($_GET['statut']) && $_GET['statut'] == 'terminee') ? 'selected' : '' ?>>Terminée</option>
                            <option value="en_cours" <?= (isset($_GET['statut']) && $_GET['statut'] == 'en_cours') ? 'selected' : '' ?>>En cours</option>
                            <option value="planifie" <?= (isset($_GET['statut']) && $_GET['statut'] == 'planifie') ? 'selected' : '' ?>>Planifiée</option>
                            <option value="annule" <?= (isset($_GET['statut']) && $_GET['statut'] == 'annule') ? 'selected' : '' ?>>Annulée</option>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date à partir de</label>
                        <input type="date" name="date_debut" class="form-control" value="<?= $_GET['date_debut'] ?? '' ?>">
                    </div>

                    <div style="display: flex; align-items: end;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            🔍 Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Liste des séances -->
        <?php if (empty($seances)): ?>
            <div style="background: white; border-radius: 16px; padding: 3rem; text-align: center;">
                <p style="font-size: 3rem; margin-bottom: 1rem;">📊</p>
                <h3 style="margin-bottom: 1rem;">Aucune séance trouvée</h3>
                <p style="color: var(--text-light);">Aucune séance ne correspond à vos filtres</p>
            </div>
        <?php else: ?>
            <div class="seances-grid">
                <?php foreach ($seances as $seance): ?>
                    <?php
                    $totalInscrits = $seance['nb_inscrits'] ?? 0;
                    $nbPresents = $seance['nb_presents'] ?? 0;
                    $tauxPresence = $totalInscrits > 0 ? round(($nbPresents / $totalInscrits) * 100, 1) : 0;

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
                    <div class="seance-card">
                        <div class="seance-header">
                            <div style="flex: 1;">
                                <div class="seance-title">
                                    <?= htmlspecialchars($seance['nom_cours']) ?>
                                </div>
                                <div style="background: rgba(8, 145, 178, 0.1); color: #0891b2; padding: 0.25rem 0.5rem; border-radius: 4px; display: inline-block; font-size: 0.75rem; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($seance['code_cours']) ?>
                                </div>
                            </div>
                            <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                        </div>

                        <div class="seance-info" style="margin-bottom: 0.75rem;">
                            📅 <?= date('d/m/Y', strtotime($seance['date_seance'])) ?> •
                            <?= substr($seance['heure_debut'], 0, 5) ?> - <?= substr($seance['heure_fin'], 0, 5) ?>
                        </div>

                        <div class="presence-stats">
                            <span class="stat-badge success">✓ <?= $nbPresents ?> présents</span>
                            <span class="stat-badge danger">✗ <?= $totalInscrits - $nbPresents ?> absents</span>
                            <span class="stat-badge info">📊 Taux: <?= $tauxPresence ?>%</span>
                        </div>

                        <div style="margin-top: auto; padding-top: 1rem;">
                            <a href="presence_detail.php?id_seance=<?= $seance['id_seance'] ?>" class="btn btn-primary" style="width: 100%; text-align: center;">
                                👁️ Voir le détail
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>