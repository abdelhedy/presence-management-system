<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/EtudiantController.php';
require_once '../../controllers/PresenceController.php';

AuthController::requireUserType('etudiant');

$etudiantController = new EtudiantController();
$presenceController = new PresenceController();
$idEtudiant = $_SESSION['etudiant_id'] ?? null;

// Debug
if (!$idEtudiant) {
    echo '<pre>Session data: ';
    print_r($_SESSION);
    echo '</pre>';
    die('ID étudiant non trouvé dans la session. Veuillez vous reconnecter.');
}

// Récupérer toutes les séances de l'étudiant (passées et à venir)
$seancesResult = $etudiantController->getAllSeancesByEtudiant($idEtudiant);
$seances = $seancesResult['success'] ? $seancesResult['seances'] : [];

// Filtrer par cours si demandé
$filtreCours = isset($_GET['cours']) ? $_GET['cours'] : null;
if ($filtreCours) {
    $seances = array_filter($seances, function ($s) use ($filtreCours) {
        return $s['id_cours'] == $filtreCours;
    });
}

// Récupérer les cours pour le filtre
$coursResult = $etudiantController->getMesCours($idEtudiant);
$mesCours = $coursResult['success'] ? $coursResult['cours'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Séances - Étudiant</title>
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

        .filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .seances-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .seance-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
        }

        .seance-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .seance-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .badge {
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
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

        .badge-info {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
        }

        .seance-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
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
            <a href="dashboard.php" class="nav-item">
                <span>📊</span> Dashboard
            </a>
            <a href="mes_cours.php" class="nav-item">
                <span>📚</span> Mes Cours
            </a>
            <a href="mes_seances.php" class="nav-item active">
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">🗓️ Mes Séances</h1>
                <p style="color: var(--text-light);">Toutes mes séances passées et à venir</p>
            </div>
        </div>

        <!-- Filtre -->
        <div class="filter-bar">
            <label style="font-weight: 600;">Filtrer par cours :</label>
            <select id="filtreCours" style="flex: 1; max-width: 300px; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 8px;">
                <option value="">Tous les cours</option>
                <?php foreach ($mesCours as $cours): ?>
                    <option value="<?= $cours['id_cours'] ?>" <?= $filtreCours == $cours['id_cours'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cours['nom_cours']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="badge" style="background: rgba(8, 145, 178, 0.1); color: var(--primary-color); font-size: 0.95rem; padding: 0.6rem 1rem;">
                <?= count($seances) ?> séance(s)
            </span>
        </div>

        <?php if (empty($seances)): ?>
            <div class="empty-state">
                <p style="font-size: 4rem; margin-bottom: 1rem;">📅</p>
                <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Aucune séance</h3>
                <p style="color: var(--text-light);">
                    <?= $filtreCours ? 'Aucune séance trouvée pour ce cours.' : 'Aucune séance disponible pour le moment.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="seances-list">
                <?php foreach ($seances as $seance): ?>
                    <?php
                    // Déterminer le statut de présence
                    $presenceStatut = $seance['presence_statut'] ?? null;
                    if ($presenceStatut === 'present') {
                        $badgeClass = 'badge-success';
                        $badgeText = '✓ Présent';
                    } elseif ($presenceStatut === 'absent') {
                        $badgeClass = 'badge-danger';
                        $badgeText = '✗ Absent';
                    } else {
                        $badgeClass = 'badge-warning';
                        $badgeText = '⏳ Non marqué';
                    }

                    // Déterminer le statut de la séance
                    $seanceStatut = $seance['statut'];
                    if ($seanceStatut === 'en_cours') {
                        $statutClass = 'badge-info';
                        $statutText = '🔵 En cours';
                    } elseif ($seanceStatut === 'termine') {
                        $statutClass = 'badge';
                        $statutText = '⚫ Terminée';
                    } else {
                        $statutClass = 'badge-warning';
                        $statutText = '🟡 Planifiée';
                    }
                    ?>
                    <div class="seance-card">
                        <div class="seance-header">
                            <div>
                                <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">
                                    <?= htmlspecialchars($seance['nom_cours']) ?>
                                </h3>
                                <span class="badge" style="background: rgba(8, 145, 178, 0.1); color: var(--primary-color);">
                                    <?= htmlspecialchars($seance['code_cours']) ?>
                                </span>
                            </div>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <span class="<?= $statutClass ?>"><?= $statutText ?></span>
                                <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                            </div>
                        </div>

                        <div class="seance-info">
                            <span>📅 <?= date('d/m/Y', strtotime($seance['date_seance'])) ?></span>
                            <span>🕐 <?= substr($seance['heure_debut'], 0, 5) ?> - <?= substr($seance['heure_fin'], 0, 5) ?></span>
                            <span>🏫 <?= htmlspecialchars($seance['salle']) ?></span>
                            <span>👨‍🏫 <?= htmlspecialchars($seance['nom_enseignant']) ?></span>
                        </div>

                        <?php if ($seanceStatut === 'en_cours' && !$presenceStatut): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                                <a href="marquer_presence.php?id_seance=<?= $seance['id_seance'] ?>"
                                    class="btn btn-primary" style="width: 100%;">
                                    ✓ Marquer ma présence maintenant
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('filtreCours').addEventListener('change', function() {
            const coursId = this.value;
            if (coursId) {
                window.location.href = 'mes_seances.php?cours=' + coursId;
            } else {
                window.location.href = 'mes_seances.php';
            }
        });
    </script>
</body>

</html>