<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SeanceController.php';
require_once '../../controllers/CoursController.php';

AuthController::requireUserType('enseignant');

$seanceController = new SeanceController();
$coursController = new CoursController();
$idEnseignant = $_SESSION['enseignant_id'];

// Récupérer l'ID du cours
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: mes_cours.php');
    exit;
}

$idCours = intval($_GET['id']);

// Récupérer les informations du cours
$coursResult = $coursController->getById($idCours);
if (!$coursResult['success']) {
    header('Location: mes_cours.php');
    exit;
}
$cours = $coursResult['cours'];

// Vérifier que le cours appartient à l'enseignant
if ($cours->id_enseignant != $idEnseignant) {
    header('Location: mes_cours.php');
    exit;
}

// Récupérer toutes les séances du cours
$seancesResult = $seanceController->getByCours($idCours);
$seances = $seancesResult['success'] ? $seancesResult['seances'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Séances de <?= htmlspecialchars($cours->nom_cours) ?> - Système de Présence</title>
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

        .cours-header {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .seances-list {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .seance-item {
            padding: 1rem;
            border-bottom: 1px solid var(--light-bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .seance-item:last-child {
            border-bottom: none;
        }

        .seance-info h3 {
            color: var(--dark-bg);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .seance-details {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-planifie {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .badge-en-cours {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .badge-termine {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .badge-annule {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .cours-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
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
        <div style="margin-bottom: 2rem;">
            <a href="mes_cours.php" style="color: var(--primary-color); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                ← Retour à mes cours
            </a>
        </div>

        <!-- Cours Header -->
        <div class="cours-header">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div>
                    <h1 style="font-size: 2rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($cours->nom_cours) ?></h1>
                    <p style="color: var(--text-light); margin-bottom: 1rem;">
                        Code: <?= htmlspecialchars($cours->code_cours) ?> |
                        <?= htmlspecialchars($cours->niveau) ?> - <?= htmlspecialchars($cours->specialite) ?>
                    </p>
                    <?php if (!empty($cours->description)): ?>
                        <p style="color: var(--text-light);"><?= htmlspecialchars($cours->description) ?></p>
                    <?php endif; ?>
                    <div class="cours-stats">
                        <div class="stat-item">
                            <span>👥</span>
                            <span><?= $cours->nb_etudiants ?? 0 ?> étudiants inscrits</span>
                        </div>
                        <div class="stat-item">
                            <span>🗓️</span>
                            <span><?= count($seances) ?> séance(s)</span>
                        </div>
                    </div>
                </div>
                <a href="planifier_seance.php?id_cours=<?= $idCours ?>" class="btn btn-primary">
                    ➕ Planifier une séance
                </a>
            </div>
        </div>

        <!-- Séances List -->
        <div style="margin-bottom: 1rem;">
            <h2 style="font-size: 1.5rem;">📋 Séances</h2>
        </div>

        <?php if (empty($seances)): ?>
            <div class="seances-list">
                <div class="empty-state">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                    <h3>Aucune séance planifiée pour ce cours</h3>
                    <p style="color: var(--text-light); margin-bottom: 2rem;">Planifiez la première séance de ce cours</p>
                    <a href="planifier_seance.php?id_cours=<?= $idCours ?>" class="btn btn-primary">➕ Planifier une séance</a>
                </div>
            </div>
        <?php else: ?>
            <div class="seances-list">
                <?php foreach ($seances as $seance): ?>
                    <div class="seance-item">
                        <div class="seance-info">
                            <h3>Séance du <?= date('d/m/Y', strtotime($seance['date_seance'])) ?></h3>
                            <div class="seance-details">
                                <span>🕐 <?= substr($seance['heure_debut'], 0, 5) ?> - <?= substr($seance['heure_fin'], 0, 5) ?></span>
                                <span>🏫 <?= htmlspecialchars($seance['salle']) ?></span>
                                <span>📝 <?= htmlspecialchars($seance['type_seance']) ?></span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span class="badge badge-<?= $seance['statut'] ?>">
                                <?= ucfirst($seance['statut']) ?>
                            </span>
                            <?php if (isset($seance['nb_presents']) && isset($seance['nb_inscrits'])): ?>
                                <span style="color: var(--text-light); font-size: 0.9rem;">
                                    <?= $seance['nb_presents'] ?? 0 ?> / <?= $seance['nb_inscrits'] ?? 0 ?> présents
                                </span>
                            <?php endif; ?>
                            <?php if ($seance['statut'] == 'termine' || $seance['statut'] == 'terminé' || $seance['statut'] == 'Terminé'): ?>
                                <a href="presence_detail.php?id_seance=<?= intval($seance['id_seance']) ?>&amp;from_cours=<?= intval($idCours) ?>"
                                    class="btn btn-secondary"
                                    style="padding: 0.5rem 1rem; text-decoration: none; display: inline-block;">
                                    👁️ Voir les présences
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>