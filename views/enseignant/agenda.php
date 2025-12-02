<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SeanceController.php';
require_once '../../controllers/CoursController.php';

AuthController::requireUserType('enseignant');

$seanceController = new SeanceController();
$coursController = new CoursController();
$idEnseignant = $_SESSION['enseignant_id'];

// Récupérer le mois et l'année (défaut: mois actuel)
$mois = isset($_GET['mois']) ? intval($_GET['mois']) : date('n');
$annee = isset($_GET['annee']) ? intval($_GET['annee']) : date('Y');

// Calculer le premier et dernier jour du mois
$premierJour = date('Y-m-01', strtotime("$annee-$mois-01"));
$dernierJour = date('Y-m-t', strtotime("$annee-$mois-01"));

// Récupérer toutes les séances du mois
$filters = [
    'date_debut' => $premierJour,
    'date_fin' => $dernierJour
];
$seancesResult = $seanceController->getByEnseignant($idEnseignant, $filters);
$seances = $seancesResult['success'] ? $seancesResult['seances'] : [];

// Récupérer les cours pour le filtre
$coursResult = $coursController->getMesCours($idEnseignant);
$mesCours = $coursResult['success'] ? $coursResult['cours'] : [];

// Organiser les séances par date
$seancesParDate = [];
foreach ($seances as $seance) {
    $date = $seance['date_seance'];
    if (!isset($seancesParDate[$date])) {
        $seancesParDate[$date] = [];
    }
    $seancesParDate[$date][] = $seance;
}

// Calculer le calendrier
$premierJourSemaine = date('N', strtotime($premierJour)); // 1 (lundi) à 7 (dimanche)
$nbJours = date('t', strtotime($premierJour));

// Navigation mois
$moisPrecedent = date('n', strtotime("$annee-$mois-01 -1 month"));
$anneePrecedente = date('Y', strtotime("$annee-$mois-01 -1 month"));
$moisSuivant = date('n', strtotime("$annee-$mois-01 +1 month"));
$anneeSuivante = date('Y', strtotime("$annee-$mois-01 +1 month"));

// Noms des mois en français
$nomsMois = [
    1 => 'Janvier',
    2 => 'Février',
    3 => 'Mars',
    4 => 'Avril',
    5 => 'Mai',
    6 => 'Juin',
    7 => 'Juillet',
    8 => 'Août',
    9 => 'Septembre',
    10 => 'Octobre',
    11 => 'Novembre',
    12 => 'Décembre'
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda - Système de Présence</title>
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

        .calendar-header {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .calendar-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .calendar-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .calendar-day {
            min-height: 120px;
            border: 1px solid var(--light-bg);
            border-radius: 8px;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .calendar-day:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(8, 145, 178, 0.2);
        }

        .calendar-day.empty {
            background: #fafafa;
            cursor: default;
        }

        .calendar-day.empty:hover {
            border-color: var(--light-bg);
            box-shadow: none;
        }

        .calendar-day.today {
            border: 2px solid var(--primary-color);
            background: rgba(8, 145, 178, 0.05);
        }

        .day-number {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-bg);
        }

        .seance-item {
            background: var(--primary-color);
            color: white;
            padding: 0.3rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-bottom: 0.3rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .seance-item:hover {
            opacity: 0.8;
            transform: translateX(2px);
        }

        .seance-item.planifie {
            background: #3b82f6;
        }

        .seance-item.en_cours {
            background: #f59e0b;
        }

        .seance-item.termine {
            background: #10b981;
        }

        .seance-item.annule {
            background: #ef4444;
        }

        .legende {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--light-bg);
        }

        .legende-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .legende-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
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
            <a href="agenda.php" class="nav-item active">
                <span>🗓️</span> Agenda
            </a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
                <span>🚪</span> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">📅 Agenda</h1>
            <p style="color: var(--text-light);">Vue calendrier de vos séances</p>
        </div>

        <!-- Calendar Header -->
        <div class="calendar-header">
            <div class="calendar-nav">
                <a href="?mois=<?= $moisPrecedent ?>&annee=<?= $anneePrecedente ?>" class="btn btn-secondary">
                    ← Mois précédent
                </a>
                <h2 style="margin: 0 2rem;"><?= $nomsMois[$mois] ?> <?= $annee ?></h2>
                <a href="?mois=<?= $moisSuivant ?>&annee=<?= $anneeSuivante ?>" class="btn btn-secondary">
                    Mois suivant →
                </a>
            </div>
            <a href="?mois=<?= date('n') ?>&annee=<?= date('Y') ?>" class="btn btn-primary">
                📅 Aujourd'hui
            </a>
        </div>

        <!-- Calendar -->
        <div class="calendar-container">
            <div class="calendar-grid">
                <!-- En-têtes des jours -->
                <div class="calendar-day-header">Lun</div>
                <div class="calendar-day-header">Mar</div>
                <div class="calendar-day-header">Mer</div>
                <div class="calendar-day-header">Jeu</div>
                <div class="calendar-day-header">Ven</div>
                <div class="calendar-day-header">Sam</div>
                <div class="calendar-day-header">Dim</div>

                <!-- Jours vides avant le 1er du mois -->
                <?php for ($i = 1; $i < $premierJourSemaine; $i++): ?>
                    <div class="calendar-day empty"></div>
                <?php endfor; ?>

                <!-- Jours du mois -->
                <?php for ($jour = 1; $jour <= $nbJours; $jour++): ?>
                    <?php
                    $dateActuelle = sprintf("%04d-%02d-%02d", $annee, $mois, $jour);
                    $isToday = $dateActuelle === date('Y-m-d');
                    $seancesDuJour = $seancesParDate[$dateActuelle] ?? [];
                    ?>
                    <div class="calendar-day <?= $isToday ? 'today' : '' ?>">
                        <div class="day-number"><?= $jour ?></div>
                        <?php foreach ($seancesDuJour as $seance): ?>
                            <div class="seance-item <?= $seance['statut'] ?>"
                                onclick="window.location.href='presence_detail.php?id_seance=<?= $seance['id_seance'] ?>'"
                                title="<?= htmlspecialchars($seance['nom_cours']) ?> - <?= substr($seance['heure_debut'], 0, 5) ?>">
                                <?= substr($seance['heure_debut'], 0, 5) ?> - <?= htmlspecialchars(substr($seance['code_cours'], 0, 10)) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>

                <!-- Jours vides après le dernier jour du mois -->
                <?php
                $dernierJourSemaine = date('N', strtotime($dernierJour));
                for ($i = $dernierJourSemaine; $i < 7; $i++):
                ?>
                    <div class="calendar-day empty"></div>
                <?php endfor; ?>
            </div>

            <!-- Légende -->
            <div class="legende">
                <div class="legende-item">
                    <div class="legende-color" style="background: #3b82f6;"></div>
                    <span>Planifiée</span>
                </div>
                <div class="legende-item">
                    <div class="legende-color" style="background: #f59e0b;"></div>
                    <span>En cours</span>
                </div>
                <div class="legende-item">
                    <div class="legende-color" style="background: #10b981;"></div>
                    <span>Terminée</span>
                </div>
                <div class="legende-item">
                    <div class="legende-color" style="background: #ef4444;"></div>
                    <span>Annulée</span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>