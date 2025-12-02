<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/PresenceController.php';

AuthController::requireUserType('etudiant');

$presenceController = new PresenceController();
$idEtudiant = $_SESSION['etudiant_id'];

// Filtres
$filters = [];
if (!empty($_GET['mois'])) {
    $filters['mois'] = $_GET['mois'];
}

// Récupérer l'historique via le controller
$historiqueResult = $presenceController->getHistoriqueEtudiant($idEtudiant, $filters);
$historique = $historiqueResult['success'] ? $historiqueResult['historique'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Historique - Système de Présence</title>
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

        .card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--light-bg);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            border-bottom: 2px solid var(--border-color);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table tr:hover {
            background: rgba(8, 145, 178, 0.05);
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

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2 style="font-size: 1.3rem;">🎓 Étudiant</h2>
            <p style="color: rgba(255,255,255,0.6); font-size: 0.9rem;"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
        </div>

        <nav>
            <a href="dashboard.php" class="nav-item">
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
            <a href="historique.php" class="nav-item active">
                <span>📜</span> Historique
            </a>
            <a href="profil.php" class="nav-item">
                <span>👤</span> Mon Profil
            </a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem;">
                <span>🚪</span> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">📜 Mon Historique de Présence</h1>
                <p style="color: var(--text-light);">Consultez toutes vos présences et absences</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card">
            <form method="GET" style="display: flex; gap: 1rem; align-items: end;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Filtrer par mois</label>
                    <input type="month" name="mois" class="form-control" value="<?= $_GET['mois'] ?? '' ?>">
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                <?php if (!empty($_GET['mois'])): ?>
                    <a href="historique.php" class="btn btn-outline">Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tableau d'historique -->
        <div class="card">
            <?php if (empty($historique)): ?>
                <div style="text-align: center; padding: 3rem 0;">
                    <p style="font-size: 3rem; margin-bottom: 1rem;">📋</p>
                    <h3 style="margin-bottom: 0.5rem;">Aucun enregistrement</h3>
                    <p style="color: var(--text-light);">Aucune présence n'a été enregistrée pour cette période</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Cours</th>
                                <th>Horaire</th>
                                <th>Statut</th>
                                <th>Méthode</th>
                                <th>Marqué le</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique as $presence): ?>
                                <?php
                                $badgeClass = 'badge-danger';
                                $statusText = '✗ Absent';
                                $statusIcon = '✗';

                                if ($presence['statut'] === 'present') {
                                    $badgeClass = 'badge-success';
                                    $statusText = '✓ Présent';
                                    $statusIcon = '✓';
                                } elseif ($presence['statut'] === 'justifie') {
                                    $badgeClass = 'badge-warning';
                                    $statusText = '📝 Justifié';
                                    $statusIcon = '📝';
                                }

                                $methodeText = $presence['methode_validation'] === 'image' ? '📷 Reconnaissance faciale' : '✍️ Manuel';
                                ?>
                                <tr>
                                    <td style="font-weight: 600;">
                                        <?= date('d/m/Y', strtotime($presence['date_seance'])) ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($presence['nom_cours']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--text-light);">
                                            <?= htmlspecialchars($presence['code_cours']) ?>
                                        </div>
                                    </td>
                                    <td style="color: var(--text-light);">
                                        <?= substr($presence['heure_debut'], 0, 5) ?> - <?= substr($presence['heure_fin'], 0, 5) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 0.9rem; color: var(--text-light);">
                                        <?= $methodeText ?>
                                        <?php if ($presence['methode_validation'] === 'image' && isset($presence['score_reconnaissance'])): ?>
                                            <br>
                                            <span style="font-size: 0.8rem;">
                                                (Score: <?= round($presence['score_reconnaissance'], 1) ?>%)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 0.85rem; color: var(--text-light);">
                                        <?= $presence['date_heure_marquage'] ? date('d/m/Y H:i', strtotime($presence['date_heure_marquage'])) : '-' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--border-color);">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                        <?php
                        $totalPresences = count(array_filter($historique, fn($p) => $p['statut'] === 'present'));
                        $totalAbsences = count(array_filter($historique, fn($p) => $p['statut'] === 'absent'));
                        $totalJustifies = count(array_filter($historique, fn($p) => $p['statut'] === 'justifie'));
                        $tauxPresence = count($historique) > 0 ? round(($totalPresences / count($historique)) * 100, 1) : 0;
                        ?>

                        <div style="text-align: center; padding: 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 12px;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--success); margin-bottom: 0.3rem;">
                                <?= $totalPresences ?>
                            </div>
                            <div style="color: var(--text-light); font-size: 0.9rem;">Présences</div>
                        </div>

                        <div style="text-align: center; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-radius: 12px;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--danger); margin-bottom: 0.3rem;">
                                <?= $totalAbsences ?>
                            </div>
                            <div style="color: var(--text-light); font-size: 0.9rem;">Absences</div>
                        </div>

                        <div style="text-align: center; padding: 1rem; background: rgba(245, 158, 11, 0.1); border-radius: 12px;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--warning); margin-bottom: 0.3rem;">
                                <?= $totalJustifies ?>
                            </div>
                            <div style="color: var(--text-light); font-size: 0.9rem;">Justifiées</div>
                        </div>

                        <div style="text-align: center; padding: 1rem; background: rgba(59, 130, 246, 0.1); border-radius: 12px;">
                            <div style="font-size: 2rem; font-weight: 700; color: var(--info); margin-bottom: 0.3rem;">
                                <?= $tauxPresence ?>%
                            </div>
                            <div style="color: var(--text-light); font-size: 0.9rem;">Taux de présence</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>