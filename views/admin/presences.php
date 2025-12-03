<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

AuthController::requireUserType('administrateur');

$adminController = new AdminController();
$presencesResult = $adminController->getPresencesDetaillees();
$presences = $presencesResult['success'] ? $presencesResult['presences'] : [];
$error = !$presencesResult['success'] ? ($presencesResult['error'] ?? 'Erreur inconnue') : '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Présences - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 12px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .nav-item {
            padding: 0.9rem 1.5rem;
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .nav-item:hover {
            background: rgba(8, 145, 178, 0.15);
            color: white;
        }

        .nav-item.active {
            background: rgba(8, 145, 178, 0.25);
            color: white;
            border-left: 3px solid #0891b2;
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 2rem;
        }

        .header {
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #64748b;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th {
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
            color: white;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-container td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .table-container tbody tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-present {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-retard {
            background: #fed7aa;
            color: #9a3412;
        }

        .info-text {
            margin-top: 1rem;
            color: #64748b;
            font-size: 0.875rem;
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
            <a href="seances.php" class="nav-item"><span>🗓️</span> Séances</a>
            <a href="presences.php" class="nav-item active"><span>✓</span> Présences</a>
            <a href="../logout.php" class="nav-item" style="margin-top:2rem;border-top:1px solid rgba(255,255,255,.1);padding-top:1.5rem"><span>🚪</span> Déconnexion</a>
        </nav>
        <div class="main-content">
            <div class="header">
                <h1>✓ Suivi des Présences</h1>
                <p>Historique complet des présences</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Cours</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Statut</th>
                            <th>Méthode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($presences)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:3rem;color:#64748b">Aucune présence enregistrée</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($presences, 0, 100) as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['nom_etudiant'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($p['nom_cours'] ?? '') ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['date_seance'])) ?></td>
                                    <td><?= substr($p['heure_debut'], 0, 5) ?></td>
                                    <td><span class="badge badge-<?= $p['statut'] ?>"><?= ucfirst($p['statut']) ?></span></td>
                                    <td><?= ucfirst($p['methode_validation'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="info-text">📊 Affichage limité aux 100 dernières présences • Total: <?= count($presences) ?> enregistrements</p>
        </div>
    </div>
</body>

</html>