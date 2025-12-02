<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../config/database.php';

AuthController::requireUserType('enseignant');

// Récupérer les données pour les sélections
$database = new Database();
$db = $database->getConnection();

// L'année scolaire actuelle sera 2024-2025 par défaut
$annee_scolaire_actuelle = '2024-2025';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Cours - Système de Présence</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
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

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            color: var(--dark-bg);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--light-bg);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--dark-bg);
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light-bg);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-help {
            background: rgba(8, 145, 178, 0.05);
            padding: 1rem;
            border-radius: 8px;
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .info-box {
            background: rgba(16, 185, 129, 0.1);
            padding: 1rem;
            border-radius: 8px;
            color: var(--success);
            margin-top: 1rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn-primary,
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #067a9c;
        }

        .btn-secondary {
            background: var(--light-bg);
            color: var(--dark-bg);
        }

        .btn-secondary:hover {
            background: #e5e7eb;
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
            <a href="create_cours.php" class="nav-item active">
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
                <span>🚺</span> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div style="margin-bottom: 2rem; text-align: center;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">➕ Créer un nouveau cours</h1>
            <p style="color: var(--text-light);">Remplissez les informations ci-dessous pour créer un cours</p>
        </div>

        <div class="form-container">
            <div id="message" class="message" style="display: none;"></div>

            <form id="createCoursForm" class="form-card">
                <div class="form-section">
                    <h3>📋 Informations du cours</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom_cours">📚 Nom du cours *</label>
                            <input type="text" id="nom_cours" name="nom_cours" required
                                placeholder="Ex: Développement Web Avancé" class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="code_cours">🔢 Code du cours *</label>
                            <input type="text" id="code_cours" name="code_cours" required
                                placeholder="Ex: DEV301" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">📝 Description</label>
                        <textarea id="description" name="description" rows="4"
                            placeholder="Description détaillée du cours..." class="form-control"></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>👥 Public cible</h3>

                    <div class="form-group">
                        <label for="specialite">📖 Spécialité / Formation *</label>
                        <select id="specialite" name="specialite" required class="form-control" onchange="updateNiveaux()">
                            <option value="">Sélectionner une spécialité...</option>

                            <optgroup label="🎓 Licence">
                                <option value="Licence TIC">Licence en Technologies de l'Information et de la Communication (L-TIC)</option>
                            </optgroup>

                            <optgroup label="👨‍💻 Cycle Ingénieur">
                                <option value="Génie Électronique">Génie des Systèmes Électroniques de Communication (GEC)</option>
                                <option value="Génie Télécommunications">Génie des Télécommunications (GT)</option>
                                <option value="Génie Informatique Industrielle">Génie Informatique Industrielle (GII)</option>
                                <option value="Ingénierie Données">Ingénierie des Données et Systèmes Décisionnels (IDSD)</option>
                            </optgroup>

                            <optgroup label="🎯 Mastère Professionnel">
                                <option value="MP RITEL">MP Réseaux Informatiques et Télécommunications (RITEL)</option>
                                <option value="MP Systèmes Embarqués">MP Systèmes Embarqués (SE)</option>
                                <option value="MP Industriel">MP Vision Robotique et Systèmes Industriels (II)</option>
                                <option value="MP Auto-Aéro">MP Ingénierie Automobile et Aéronautique</option>
                            </optgroup>

                            <optgroup label="🔬 Mastère de Recherche">
                                <option value="MR STIC">MR Technologies de l'Information et de la Communication (STIC)</option>
                                <option value="MR ISI">MR Ingénierie des Systèmes Informatiques (ISI)</option>
                            </optgroup>

                            <optgroup label="📖 Doctorat">
                                <option value="Doctorat">Doctorat en Sciences et Technologies</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="niveau">🎓 Niveau *</label>
                        <select id="niveau" name="niveau" required class="form-control" disabled>
                            <option value="">Sélectionner d'abord une spécialité...</option>
                        </select>
                    </div>

                    <input type="hidden" name="annee_scolaire" value="<?= $annee_scolaire_actuelle ?>">
                </div>

                <div class="form-actions">
                    <button type="button" onclick="window.location.href='dashboard.php'" class="btn btn-secondary">
                        ✖ Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        ✓ Créer le cours
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Configuration des niveaux selon la spécialité (même logique que register.php)
        const niveauxParSpecialite = {
            'Licence TIC': ['1ère année', '2ème année', '3ème année'],

            'Génie Électronique': ['1ère année', '2ème année', '3ème année'],
            'Génie Télécommunications': ['1ère année', '2ème année', '3ème année'],
            'Génie Informatique Industrielle': ['1ère année', '2ème année', '3ème année'],
            'Ingénierie Données': ['1ère année', '2ème année', '3ème année'],

            'MP RITEL': ['1ère année', '2ème année'],
            'MP Systèmes Embarqués': ['1ère année', '2ème année'],
            'MP Industriel': ['1ère année', '2ème année'],
            'MP Auto-Aéro': ['1ère année', '2ème année'],

            'MR STIC': ['1ère année', '2ème année'],
            'MR ISI': ['1ère année', '2ème année'],

            'Doctorat': ['1ère année', '2ème année', '3ème année', '4ème année', '5ème année']
        };

        // Mettre à jour les niveaux selon la spécialité choisie
        function updateNiveaux() {
            const specialiteSelect = document.getElementById('specialite');
            const niveauSelect = document.getElementById('niveau');
            const specialite = specialiteSelect.value;

            // Réinitialiser les niveaux
            niveauSelect.innerHTML = '<option value="">Sélectionner...</option>';

            if (specialite && niveauxParSpecialite[specialite]) {
                const niveaux = niveauxParSpecialite[specialite];
                niveaux.forEach(niveau => {
                    const option = document.createElement('option');
                    option.value = niveau;
                    option.textContent = niveau;
                    niveauSelect.appendChild(option);
                });
                niveauSelect.disabled = false;
            } else {
                niveauSelect.innerHTML = '<option value="">Sélectionner d\'abord une spécialité...</option>';
                niveauSelect.disabled = true;
            }

        }

        // Soumettre le formulaire
        document.getElementById('createCoursForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const messageDiv = document.getElementById('message');

            // Ajouter l'ID de l'enseignant (utiliser enseignant_id au lieu de user_id)
            formData.append('id_enseignant', '<?php echo $_SESSION['enseignant_id']; ?>');

            // Debug: afficher les données envoyées
            console.log('Données envoyées:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ': ' + value);
            }

            fetch('../../controllers/CoursController.php?action=create', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    messageDiv.style.display = 'block';

                    if (data.success) {
                        messageDiv.className = 'message message-success';
                        messageDiv.innerHTML = `
                    <i class="fas fa-check-circle"></i>
                    ${data.message}
                `;

                        // Rediriger vers la liste des cours après 1.5 secondes
                        setTimeout(() => {
                            window.location.href = 'mes_cours.php';
                        }, 1500);
                    } else {
                        messageDiv.className = 'message message-error';
                        const errorMsg = data.error || data.message || 'Erreur lors de la création du cours';
                        messageDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${errorMsg}`;
                    }
                })
                .catch(error => {
                    messageDiv.style.display = 'block';
                    messageDiv.className = 'message message-error';
                    messageDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> Erreur: ${error.message}`;
                });
        });
    </script>
</body>

</html>