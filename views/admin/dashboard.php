<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

AuthController::requireUserType('administrateur');

$adminController = new AdminController();
$statsResult = $adminController->getDashboardStats();
$stats = $statsResult['success'] ? $statsResult['stats'] : [];

// Calculer les statistiques
$totalUtilisateurs = 0;
$totalEtudiants = 0;
$totalEnseignants = 0;
$totalAdmins = 0;

if (isset($stats['utilisateurs'])) {
    foreach ($stats['utilisateurs'] as $type => $count) {
        $totalUtilisateurs += $count;
        if ($type === 'etudiant') $totalEtudiants = $count;
        if ($type === 'enseignant') $totalEnseignants = $count;
        if ($type === 'administrateur') $totalAdmins = $count;
    }
}

$totalCours = $stats['cours']['total'] ?? 0;
$totalSeances = $stats['seances']['total'] ?? 0;
$seancesEnCours = $stats['seances']['en_cours'] ?? 0;
$seancesPlanifiees = $stats['seances']['planifie'] ?? 0;
$seancesTerminees = $stats['seances']['terminee'] ?? 0;

$totalPresences = $stats['presences']['total'] ?? 0;
$totalPresents = $stats['presences']['presents'] ?? 0;
$totalAbsents = $stats['presences']['absents'] ?? 0;
$tauxPresence = $stats['presences']['taux_global'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Système de Présence</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue {
            background: rgba(59, 130, 246, 0.1);
        }

        .stat-icon.green {
            background: rgba(16, 185, 129, 0.1);
        }

        .stat-icon.purple {
            background: rgba(139, 92, 246, 0.1);
        }

        .stat-icon.orange {
            background: rgba(249, 115, 22, 0.1);
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .stat-detail {
            font-size: 0.875rem;
            color: #64748b;
        }

        .chart-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .card h2 {
            font-size: 1.25rem;
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            padding: 1.25rem;
            background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
            color: white;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(8, 145, 178, 0.25);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.35);
        }

        .stats-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
        }

        .mini-stat {
            padding: 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }

        .mini-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .mini-stat-value.green {
            color: #10b981;
        }

        .mini-stat-value.red {
            color: #ef4444;
        }

        .mini-stat-value.blue {
            color: #3b82f6;
        }

        .mini-stat-value.orange {
            color: #f59e0b;
        }

        .mini-stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }

        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s;
        }

        @media (max-width: 1024px) {
            .chart-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>⚙️ Administration</h2>
                <p style="font-size: 0.9rem; color: rgba(255,255,255,0.6);">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </p>
            </div>

            <a href="dashboard.php" class="nav-item active">
                <span>📊</span> Dashboard
            </a>
            <a href="utilisateurs.php" class="nav-item">
                <span>👥</span> Utilisateurs
            </a>
            <a href="ajouter_utilisateur.php" class="nav-item">
                <span>➕</span> Ajouter Utilisateur
            </a>
            <a href="cours.php" class="nav-item">
                <span>📚</span> Cours
            </a>
            <a href="seances.php" class="nav-item">
                <span>🗓️</span> Séances
            </a>
            <a href="presences.php" class="nav-item">
                <span>✓</span> Présences
            </a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
                <span>🚪</span> Déconnexion
            </a>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>📊 Dashboard Administrateur</h1>
                <p>Vue d'ensemble du système de gestion de présence</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <!-- Card Utilisateurs -->
                <div class="stat-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div class="stat-icon blue" style="margin: 0; width: 40px; height: 40px; font-size: 1.25rem;">👥</div>
                            <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Utilisateurs</div>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #0891b2;"><?= $totalUtilisateurs ?></div>
                    </div>
                    <div style="font-size: 0.875rem; color: #64748b; padding-left: 0.5rem;">
                        <span style="color: #3b82f6; font-weight: 600;"><?= $totalEtudiants ?></span> étudiants •
                        <span style="color: #8b5cf6; font-weight: 600;"><?= $totalEnseignants ?></span> enseignants
                    </div>
                </div>

                <!-- Card Cours -->
                <div class="stat-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div class="stat-icon green" style="margin: 0; width: 40px; height: 40px; font-size: 1.25rem;">📚</div>
                            <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Cours Actifs</div>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #0891b2;"><?= $totalCours ?></div>
                    </div>
                    <div style="font-size: 0.875rem; color: #059669; font-weight: 600; padding-left: 0.5rem;">
                        ✓ Tous les cours disponibles
                    </div>
                </div>

                <!-- Card Séances -->
                <div class="stat-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div class="stat-icon purple" style="margin: 0; width: 40px; height: 40px; font-size: 1.25rem;">🗓️</div>
                            <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Séances</div>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: #0891b2;"><?= $totalSeances ?></div>
                    </div>
                    <div style="font-size: 0.875rem; color: #64748b; padding-left: 0.5rem;">
                        <span style="color: #10b981; font-weight: 600;"><?= $seancesEnCours ?></span> en cours •
                        <span style="color: #3b82f6; font-weight: 600;"><?= $seancesPlanifiees ?></span> planifiées
                    </div>
                </div>

                <!-- Card Taux de Présence -->
                <div class="stat-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div class="stat-icon orange" style="margin: 0; width: 40px; height: 40px; font-size: 1.25rem;">✓</div>
                            <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Taux de Présence</div>
                        </div>
                        <div style="font-size: 2rem; font-weight: 700; color: <?= $tauxPresence >= 75 ? '#10b981' : ($tauxPresence >= 50 ? '#f59e0b' : '#ef4444') ?>"><?= round($tauxPresence, 1) ?>%</div>
                    </div>
                    <div style="font-size: 0.875rem; color: #64748b; padding-left: 0.5rem; margin-bottom: 0.75rem;">
                        <span style="color: #10b981; font-weight: 600;"><?= $totalPresents ?></span> présents •
                        <span style="color: #ef4444; font-weight: 600;"><?= $totalAbsents ?></span> absents
                    </div>
                    <div class="progress-bar" style="margin-top: 0;">
                        <div class="progress-fill" style="width: <?= $tauxPresence ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Charts Section Row 1 -->
            <div class="chart-section">
                <div class="card">
                    <h2>📊 Répartition des Présences</h2>
                    <canvas id="presencesChart" style="max-height: 280px;"></canvas>
                </div>

                <div class="card">
                    <h2>🗓️ Statut des Séances</h2>
                    <canvas id="seancesChart" style="max-height: 280px;"></canvas>
                </div>
            </div>

            <!-- Charts Section Row 2 -->
            <div class="chart-section">
                <div class="card">
                    <h2>📚 Top 6 Cours - Présences</h2>
                    <canvas id="coursChart" style="max-height: 300px;"></canvas>
                </div>

                <div class="card">
                    <h2>👥 Répartition des Utilisateurs</h2>
                    <canvas id="utilisateursChart" style="max-height: 280px;"></canvas>
                </div>
            </div>

            <!-- Charts Section Row 3 -->
            <div class="chart-section">
                <div class="card">
                    <h2>📈 Évolution des Présences (7 derniers jours)</h2>
                    <canvas id="evolutionChart" style="max-height: 300px;"></canvas>
                </div>

                <div class="card">
                    <h2>✓ Méthodes de Validation</h2>
                    <canvas id="methodesChart" style="max-height: 280px;"></canvas>
                </div>
            </div>

            <!-- Taux de présence par cours -->
            <div class="card" style="margin-bottom: 2rem;">
                <h2>🎯 Taux de Présence par Cours (Top 5)</h2>
                <canvas id="tauxCoursChart" style="max-height: 350px;"></canvas>
            </div>

            <!-- Actions Rapides -->
            <div class="card">
                <h2>🚀 Actions Rapides</h2>
                <div class="quick-actions">
                    <a href="ajouter_utilisateur.php" class="action-btn">
                        ➕ Ajouter Utilisateur
                    </a>
                    <a href="utilisateurs.php" class="action-btn">
                        👥 Gérer Utilisateurs
                    </a>
                    <a href="presences.php" class="action-btn">
                        ✓ Voir Présences
                    </a>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card">
                <h2>ℹ️ Informations Système</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 3px solid #3b82f6;">
                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;">Base de données</div>
                        <div style="font-weight: 600; color: #1e293b;">MySQL</div>
                    </div>
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 3px solid #10b981;">
                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;">Reconnaissance Faciale</div>
                        <div style="font-weight: 600; color: #1e293b;">DeepFace + Facenet</div>
                    </div>
                    <div style="padding: 1rem; background: #f8fafc; border-radius: 8px; border-left: 3px solid #f59e0b;">
                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.25rem;">Version</div>
                        <div style="font-weight: 600; color: #1e293b;">1.0.0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration des couleurs
        const colors = {
            primary: '#0891b2',
            success: '#10b981',
            danger: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6',
            purple: '#8b5cf6',
            orange: '#f97316',
            cyan: '#06b6d4',
            emerald: '#059669',
            rose: '#f43f5e'
        };

        // Chart 1: Répartition des Présences (Doughnut)
        const presencesCtx = document.getElementById('presencesChart').getContext('2d');
        new Chart(presencesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Présents', 'Absents', 'Justifiés'],
                datasets: [{
                    data: [<?= $totalPresents ?>, <?= $totalAbsents ?>, <?= $stats['presences']['justifies'] ?? 0 ?>],
                    backgroundColor: [colors.success, colors.danger, colors.warning],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = <?= $totalPresences ?>;
                                const value = context.parsed;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Chart 2: Statut des Séances (Bar)
        const seancesCtx = document.getElementById('seancesChart').getContext('2d');
        new Chart(seancesCtx, {
            type: 'bar',
            data: {
                labels: ['En Cours', 'Planifiées', 'Terminées'],
                datasets: [{
                    label: 'Nombre de séances',
                    data: [<?= $seancesEnCours ?>, <?= $seancesPlanifiees ?>, <?= $seancesTerminees ?>],
                    backgroundColor: [colors.success, colors.info, colors.warning],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Chart 3: Top Cours (Bar horizontale)
        const coursCtx = document.getElementById('coursChart').getContext('2d');
        new Chart(coursCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($stats['top_cours'] as $c): ?> '<?= addslashes(substr($c['nom_cours'], 0, 25)) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                        label: 'Présents',
                        data: [<?php foreach ($stats['top_cours'] as $c): ?><?= $c['presents'] ?>, <?php endforeach; ?>],
                        backgroundColor: colors.success,
                        borderRadius: 6
                    },
                    {
                        label: 'Absents',
                        data: [<?php foreach ($stats['top_cours'] as $c): ?><?= $c['absents'] ?>, <?php endforeach; ?>],
                        backgroundColor: colors.danger,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: {
                                size: 11,
                                weight: '600'
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        stacked: false
                    }
                }
            }
        });

        // Chart 4: Répartition des Utilisateurs (Pie)
        const utilisateursCtx = document.getElementById('utilisateursChart').getContext('2d');
        new Chart(utilisateursCtx, {
            type: 'pie',
            data: {
                labels: ['Étudiants', 'Enseignants', 'Administrateurs'],
                datasets: [{
                    data: [<?= $totalEtudiants ?>, <?= $totalEnseignants ?>, <?= $totalAdmins ?>],
                    backgroundColor: [colors.info, colors.purple, colors.primary],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });

        // Chart 5: Évolution des présences (Line)
        const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
        new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach ($stats['evolution'] as $e): ?> '<?= date('d/m', strtotime($e['date'])) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                        label: 'Présents',
                        data: [<?php foreach ($stats['evolution'] as $e): ?><?= $e['presents'] ?>, <?php endforeach; ?>],
                        borderColor: colors.success,
                        backgroundColor: colors.success + '20',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Absents',
                        data: [<?php foreach ($stats['evolution'] as $e): ?><?= $e['absents'] ?>, <?php endforeach; ?>],
                        borderColor: colors.danger,
                        backgroundColor: colors.danger + '20',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: {
                                size: 11,
                                weight: '600'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Chart 6: Méthodes de validation (Polar)
        const methodesCtx = document.getElementById('methodesChart').getContext('2d');
        new Chart(methodesCtx, {
            type: 'polarArea',
            data: {
                labels: [
                    <?php foreach ($stats['par_methode'] as $m): ?> '<?= ucfirst($m['methode']) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($stats['par_methode'] as $m): ?>
                            <?= $m['total'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [colors.info, colors.purple, colors.cyan, colors.orange],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: {
                                size: 11,
                                weight: '600'
                            }
                        }
                    }
                }
            }
        });

        // Chart 7: Taux de présence par cours (Bar horizontale avec gradient)
        const tauxCoursCtx = document.getElementById('tauxCoursChart').getContext('2d');
        new Chart(tauxCoursCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($stats['taux_par_cours'] as $t): ?> '<?= addslashes(substr($t['nom_cours'], 0, 30)) ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Taux de présence (%)',
                    data: [
                        <?php foreach ($stats['taux_par_cours'] as $t): ?>
                            <?= $t['taux'] ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        if (value >= 80) return colors.success;
                        if (value >= 60) return colors.warning;
                        return colors.danger;
                    },
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Taux: ' + context.parsed.x + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>