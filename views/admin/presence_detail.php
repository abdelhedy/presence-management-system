<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';
require_once '../../controllers/SeanceController.php';

AuthController::requireUserType('administrateur');

$adminController = new AdminController();
$seanceController = new SeanceController();

// Récupérer l'ID de la séance
if (!isset($_GET['id_seance']) || !is_numeric($_GET['id_seance'])) {
    header('Location: presences.php');
    exit;
}

$idSeance = intval($_GET['id_seance']);

// Récupérer les informations de la séance
$seanceResult = $seanceController->getById($idSeance);
if (!$seanceResult['success']) {
    header('Location: presences.php');
    exit;
}
$seance = $seanceResult['seance'];

// Récupérer les présences
$presencesResult = $adminController->consulterPresencesSeance($idSeance);
$presences = $presencesResult['success'] ? $presencesResult['presences'] : [];
$stats = $presencesResult['stats'] ?? [];

$fromCours = isset($_GET['from_cours']) ? intval($_GET['from_cours']) : null;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail des Présences - <?= htmlspecialchars($seance->nom_cours) ?></title>
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

        .seance-header-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
        }

        .table-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--light-bg);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark-bg);
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-bg);
        }

        tr:hover {
            background: rgba(8, 145, 178, 0.02);
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .export-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .btn-export {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-pdf {
            background: #ef4444;
            color: white;
        }

        .btn-pdf:hover {
            background: #dc2626;
        }

        .btn-excel {
            background: #10b981;
            color: white;
        }

        .btn-excel:hover {
            background: #059669;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2 style="font-size: 1.3rem; margin-bottom: 0.5rem;">🔐 Admin</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="utilisateurs.php" class="nav-item"><span>👥</span> Utilisateurs</a>
            <a href="ajouter_utilisateur.php" class="nav-item"><span>➕</span> Ajouter Utilisateur</a>
            <a href="cours.php" class="nav-item"><span>📚</span> Cours</a>
            <a href="seances.php" class="nav-item"><span>🗓️</span> Séances</a>
            <a href="presences.php" class="nav-item active"><span>✓</span> Présences</a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;"><span>🚪</span> Déconnexion</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="margin-bottom: 2rem;">
            <a href="<?= $fromCours ? 'cours_seances.php?id=' . $fromCours : 'presences.php' ?>"
                style="color: var(--primary-color); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                ← Retour
            </a>
        </div>

        <!-- Seance Header -->
        <div class="seance-header-card">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;"><?= htmlspecialchars($seance->nom_cours) ?></h1>
            <p style="color: var(--text-light); margin-bottom: 1rem;">
                <?= htmlspecialchars($seance->code_cours) ?> |
                <?= date('d/m/Y', strtotime($seance->date_seance)) ?> |
                <?= substr($seance->heure_debut, 0, 5) ?> - <?= substr($seance->heure_fin, 0, 5) ?> |
                Salle <?= htmlspecialchars($seance->salle) ?>
            </p>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card" style="border-left-color: var(--info);">
                <div style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 0.5rem;">Total inscrits</div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--info);">
                    <?= $stats['total'] ?? 0 ?>
                </div>
            </div>
            <div class="stat-card" style="border-left-color: var(--success);">
                <div style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 0.5rem;">Présents</div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--success);">
                    <?= $stats['presents'] ?? 0 ?>
                </div>
            </div>
            <div class="stat-card" style="border-left-color: var(--danger);">
                <div style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 0.5rem;">Absents</div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--danger);">
                    <?= $stats['absents'] ?? 0 ?>
                </div>
            </div>
            <div class="stat-card" style="border-left-color: var(--primary-color);">
                <div style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 0.5rem;">Taux de présence</div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">
                    <?= $stats['taux'] ?? 0 ?>%
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <a href="export_pdf.php?id_seance=<?= $idSeance ?>" class="btn-export btn-pdf" target="_blank">
                📄 Exporter en PDF
            </a>
            <a href="export_excel.php?id_seance=<?= $idSeance ?>" class="btn-export btn-excel">
                📊 Exporter en Excel
            </a>
        </div>

        <!-- Table -->
        <div class="table-container">
            <h2 style="margin-bottom: 1.5rem;">📋 Liste des étudiants</h2>
            <?php if (empty($presences)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📊</div>
                    <p style="color: var(--text-light);">Aucune donnée de présence disponible</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Statut</th>
                            <th>Heure de marquage</th>
                            <th>Méthode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($presences as $index => $presence): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($presence['nom_complet']) ?></td>
                                <td><?= htmlspecialchars($presence['email']) ?></td>
                                <td>
                                    <?php if ($presence['statut'] === 'present'): ?>
                                        <span class="badge badge-success">✓ Présent</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">✗ Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $presence['heure_marquage'] ? date('H:i:s', strtotime($presence['heure_marquage'])) : '-' ?>
                                </td>
                                <td>
                                    <?= $presence['methode_validation'] ? ucfirst($presence['methode_validation']) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>