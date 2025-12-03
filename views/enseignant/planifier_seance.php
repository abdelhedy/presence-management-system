<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/SeanceController.php';
require_once '../../controllers/CoursController.php';

AuthController::requireUserType('enseignant');

$coursController = new CoursController();
$idEnseignant = $_SESSION['enseignant_id'];

// Récupérer les cours via le controller
$coursResult = $coursController->getMesCours($idEnseignant);
$mesCours = $coursResult['success'] ? $coursResult['cours'] : [];

// Traitement du formulaire
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seanceController = new SeanceController();
    $result = $seanceController->create($_POST);

    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
    } else {
        $message = $result['error'] ?? 'Erreur lors de la création de la séance';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planifier une Séance - Système de Présence</title>
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

        .form-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
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
            <a href="planifier_seance.php" class="nav-item active">
                <span>📅</span> Planifier une Séance
            </a>
            <a href="presences.php" class="nav-item">
                <span>✓</span> Présences
            </a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem;">
                <span>🚪</span> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="margin-bottom: 2rem; text-align: center;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">📅 Planifier une Séance</h1>
            <p style="color: var(--text-light);">Créez une nouvelle séance de cours</p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <div class="form-card">
                <?php if (empty($mesCours)): ?>
                    <div style="text-align: center; padding: 3rem 0;">
                        <p style="font-size: 3rem; margin-bottom: 1rem;">📚</p>
                        <h3 style="margin-bottom: 1rem;">Aucun cours disponible</h3>
                        <p style="color: var(--text-light); margin-bottom: 1.5rem;">
                            Vous devez d'abord créer un cours avant de planifier une séance
                        </p>
                        <a href="create_cours.php" class="btn btn-primary">
                            ➕ Créer un cours
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="id_cours">📚 Cours *</label>
                            <select name="id_cours" id="id_cours" class="form-control" required>
                                <option value="">Sélectionnez un cours</option>
                                <?php foreach ($mesCours as $cours): ?>
                                    <option value="<?= $cours['id_cours'] ?>">
                                        <?= htmlspecialchars($cours['nom_cours']) ?> (<?= htmlspecialchars($cours['code_cours']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="date_seance">📅 Date de la séance *</label>
                            <input type="date" name="date_seance" id="date_seance" class="form-control"
                                min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="heure_debut">🕐 Heure de début *</label>
                                <input type="time" name="heure_debut" id="heure_debut" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="heure_fin">🕐 Heure de fin *</label>
                                <input type="time" name="heure_fin" id="heure_fin" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="salle">🏫 Salle *</label>
                                <input type="text" name="salle" id="salle" class="form-control"
                                    placeholder="Ex: Salle A101" required>
                            </div>

                            <div class="form-group">
                                <label for="type_seance">📖 Type de séance *</label>
                                <select name="type_seance" id="type_seance" class="form-control" required>
                                    <option value="">Sélectionnez un type</option>
                                    <option value="cours">Cours</option>
                                    <option value="TD">TD (Travaux Dirigés)</option>
                                    <option value="TP">TP (Travaux Pratiques)</option>
                                    <option value="examen">Examen</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                            <a href="dashboard.php" class="btn btn-outline" style="flex: 1;">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                ✓ Planifier la séance
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const dateInput = document.getElementById('date_seance');
        const heureDebutInput = document.getElementById('heure_debut');
        const heureFinInput = document.getElementById('heure_fin');

        // Fonction pour obtenir l'heure actuelle au format HH:MM
        function getCurrentTime() {
            const now = new Date();
            return now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0');
        }

        // Fonction pour obtenir la date d'aujourd'hui au format YYYY-MM-DD
        function getTodayDate() {
            const today = new Date();
            return today.toISOString().split('T')[0];
        }

        // Fonction pour vérifier si une heure est dans le passé (avec marge de sécurité)
        function isTimePast(timeStr) {
            const now = new Date();
            const [hours, minutes] = timeStr.split(':').map(Number);
            const selectedTime = new Date();
            selectedTime.setHours(hours, minutes, 0, 0);

            // Ajouter une marge de 1 minute pour éviter les faux positifs
            return selectedTime.getTime() < (now.getTime() - 60000);
        }

        // Vérifier et bloquer les heures passées si la date est aujourd'hui
        function updateTimeConstraints() {
            const selectedDate = dateInput.value;
            const todayDate = getTodayDate();

            if (selectedDate === todayDate) {
                // Si c'est aujourd'hui, définir l'heure minimum
                const currentTime = getCurrentTime();
                heureDebutInput.min = currentTime;

                // Si l'heure de début sélectionnée est dans le passé, la réinitialiser
                if (heureDebutInput.value && isTimePast(heureDebutInput.value)) {
                    heureDebutInput.value = '';
                    heureFinInput.value = '';
                }
            } else {
                // Pour les autres jours, pas de minimum
                heureDebutInput.removeAttribute('min');
            }
        }

        // Écouter les changements de date
        dateInput.addEventListener('change', updateTimeConstraints);

        // Validation au moment du changement d'heure de début
        heureDebutInput.addEventListener('change', function() {
            const selectedDate = dateInput.value;
            const todayDate = getTodayDate();

            if (selectedDate === todayDate) {
                if (isTimePast(this.value)) {
                    alert('Impossible de planifier une séance dans le passé. Veuillez choisir une heure future.');
                    this.value = '';
                    heureFinInput.value = '';
                    return;
                }
            }

            // Suggérer automatiquement une heure de fin (+1h30)
            if (this.value && !heureFinInput.value) {
                const [hours, minutes] = this.value.split(':').map(Number);
                const endHours = hours + 1;
                const endMinutes = minutes + 30;

                const finalHours = endHours + Math.floor(endMinutes / 60);
                const finalMinutes = endMinutes % 60;

                if (finalHours < 24) {
                    heureFinInput.value = finalHours.toString().padStart(2, '0') + ':' +
                        finalMinutes.toString().padStart(2, '0');
                }
            }
        });

        // Validation finale avant soumission
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const selectedDate = dateInput.value;
            const heureDebut = heureDebutInput.value;
            const heureFin = heureFinInput.value;
            const todayDate = getTodayDate();

            // Vérifier si c'est dans le passé
            if (selectedDate === todayDate && isTimePast(heureDebut)) {
                e.preventDefault();
                alert('Impossible de planifier une séance dans le passé. Veuillez choisir une heure future.');
                return;
            }

            // Vérifier que l'heure de fin est après l'heure de début
            if (heureDebut && heureFin && heureDebut >= heureFin) {
                e.preventDefault();
                alert('L\'heure de fin doit être après l\'heure de début');
                return;
            }
        });

        // Initialiser au chargement
        updateTimeConstraints();
    </script>
</body>

</html>