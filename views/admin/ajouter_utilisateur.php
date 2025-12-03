<?php
session_start();
require_once '../../controllers/AuthController.php';

AuthController::requireUserType('administrateur');

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $authController = new AuthController();
    $result = $authController->register($_POST);

    if ($result['success']) {
        $success = "Utilisateur créé avec succès !";
        // Réinitialiser le formulaire
        $_POST = [];
    } else {
        $error = isset($result['error']) ? $result['error'] : implode(', ', $result['errors'] ?? []);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Utilisateur - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
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
            flex: 1;
            padding: 2rem;
            background: var(--light-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-card {
            background: white;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            max-width: 700px;
            width: 100%;
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
            <a href="dashboard.php" class="nav-item">
                <span>📊</span> Dashboard
            </a>
            <a href="utilisateurs.php" class="nav-item">
                <span>👥</span> Utilisateurs
            </a>
            <a href="ajouter_utilisateur.php" class="nav-item active">
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
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">➕ Ajouter un Utilisateur</h1>
            <p style="color: var(--text-light); margin-bottom: 2rem;">Créer un nouveau compte (étudiant ou enseignant)</p>

            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="registerForm">
                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" class="form-control" required value="<?= $_POST['nom'] ?? '' ?>">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" class="form-control" required value="<?= $_POST['prenom'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required value="<?= $_POST['email'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" class="form-control" placeholder="+216 XX XXX XXX" value="<?= $_POST['telephone'] ?? '' ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Type d'utilisateur *</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="type_utilisateur" value="etudiant" checked onchange="toggleFields()">
                                <span>Étudiant</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="type_utilisateur" value="enseignant" onchange="toggleFields()">
                                <span>Enseignant</span>
                            </label>
                        </div>
                    </div>

                    <!-- Champs étudiant -->
                    <div id="etudiantFields">
                        <div class="form-info" style="background: rgba(8,145,178,0.1); padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem;">
                            <strong>📚 Informations étudiant</strong>
                        </div>

                        <div class="form-group">
                            <label for="numero_etudiant">Numéro étudiant *</label>
                            <input type="text" id="numero_etudiant" name="numero_etudiant" class="form-control" value="<?= $_POST['numero_etudiant'] ?? '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="specialite">Spécialité *</label>
                            <select id="specialite" name="specialite" class="form-control">
                                <option value="">Sélectionner...</option>
                                <optgroup label="🎓 Licence">
                                    <option value="Licence TIC">Licence TIC</option>
                                </optgroup>
                                <optgroup label="👨‍💻 Cycle Ingénieur">
                                    <option value="Génie Électronique">Génie Électronique (GEC)</option>
                                    <option value="Génie Télécommunications">Génie Télécommunications (GT)</option>
                                    <option value="Génie Informatique Industrielle">Génie Informatique Industrielle (GII)</option>
                                    <option value="Ingénierie Données">Ingénierie Données (IDSD)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="niveau">Niveau *</label>
                            <select id="niveau" name="niveau" class="form-control">
                                <option value="">Sélectionner...</option>
                                <option value="L1">Licence 1</option>
                                <option value="L2">Licence 2</option>
                                <option value="L3">Licence 3</option>
                                <option value="M1">Master 1</option>
                                <option value="M2">Master 2</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="annee_scolaire">Année scolaire *</label>
                            <input type="text" id="annee_scolaire" name="annee_scolaire" class="form-control" value="2025-2026" placeholder="2025-2026">
                        </div>
                    </div>

                    <!-- Champs enseignant -->
                    <div id="enseignantFields" style="display: none;">
                        <div class="form-info" style="background: rgba(8,145,178,0.1); padding: 0.8rem; border-radius: 8px; margin-bottom: 1rem;">
                            <strong>👨‍🏫 Informations enseignant</strong>
                        </div>

                        <div class="form-group">
                            <label for="departement">Département *</label>
                            <input type="text" id="departement" name="departement" class="form-control" placeholder="Ex: Informatique">
                        </div>

                        <div class="form-group">
                            <label for="grade">Grade</label>
                            <input type="text" id="grade" name="grade" class="form-control" placeholder="Ex: Professeur">
                        </div>

                        <div class="form-group">
                            <label for="bureau">Bureau</label>
                            <input type="text" id="bureau" name="bureau" class="form-control" placeholder="Ex: B201">
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            ✓ Créer l'utilisateur
                        </button>
                        <a href="utilisateurs.php" class="btn btn-secondary" style="flex: 0.3; text-align: center; padding: 0.8rem;">
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleFields() {
            const type = document.querySelector('input[name="type_utilisateur"]:checked').value;
            const etudiantFields = document.getElementById('etudiantFields');
            const enseignantFields = document.getElementById('enseignantFields');

            if (type === 'etudiant') {
                etudiantFields.style.display = 'block';
                enseignantFields.style.display = 'none';

                // Rendre les champs étudiants requis
                document.getElementById('numero_etudiant').required = true;
                document.getElementById('specialite').required = true;
                document.getElementById('niveau').required = true;
                document.getElementById('annee_scolaire').required = true;

                // Enlever requis pour enseignant
                document.getElementById('departement').required = false;
            } else {
                etudiantFields.style.display = 'none';
                enseignantFields.style.display = 'block';

                // Rendre les champs enseignant requis
                document.getElementById('departement').required = true;

                // Enlever requis pour étudiant
                document.getElementById('numero_etudiant').required = false;
                document.getElementById('specialite').required = false;
                document.getElementById('niveau').required = false;
                document.getElementById('annee_scolaire').required = false;
            }
        }
    </script>
</body>

</html>