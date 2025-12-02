<?php
session_start();
require_once '../controllers/AuthController.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // DEBUG - Afficher les données reçues
    // echo "<pre style='background: #f0f0f0; padding: 10px; margin: 10px; border: 2px solid red;'>";
    // echo "DONNÉES REÇUES:\n";
    // print_r($_POST);
    // echo "</pre>";
    $authController = new AuthController();
    $result = $authController->register($_POST);

    if ($result['success']) {
        // Redirection automatique si inscription réussie
        if (isset($result['redirect'])) {
            header("Location: " . $result['redirect']);
            exit();
        }
        $success = $result['message'];
    } else {
        $error = isset($result['error']) ? $result['error'] : implode(', ', $result['errors'] ?? []);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="register-page">
    <div class="register-container">
        <div class="register-box">
            <div class="register-header">
                <h2>Inscription</h2>
                <p>Créez votre compte</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <br><a href="login.php">Cliquez ici pour vous connecter</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="telephone">Téléphone</label>
                    <input type="tel" id="telephone" name="telephone" class="form-control"
                        placeholder="+216 XX XXX XXX">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Type d'utilisateur *</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="type_utilisateur" value="etudiant"
                                checked onchange="toggleFields()">
                            <span>Étudiant</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="type_utilisateur" value="enseignant"
                                onchange="toggleFields()">
                            <span>Enseignant</span>
                        </label>
                    </div>
                </div>

                <!-- Champs étudiant -->
                <div id="etudiantFields">
                    <div class="form-info">
                        <strong>📚 Informations étudiant</strong>
                    </div>
                    <div class="form-group">
                        <label for="numero_etudiant">Numéro étudiant *</label>
                        <input type="text" id="numero_etudiant" name="numero_etudiant" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="specialite">Spécialité / Formation *</label>
                        <select id="specialite" name="specialite" class="form-control" onchange="updateNiveaux()">
                            <option value="">Sélectionner une spécialité...</option>

                            <optgroup label="🎓 Licence ">
                                <option value="Licence TIC">Licence en Technologies de l'Information et de la Communication (L-TIC)</option>
                            </optgroup>

                            <optgroup label="👨‍💻 Cycle Ingénieur ">
                                <option value="Génie Électronique">Génie des Systèmes Électroniques de Communication (GEC)</option>
                                <option value="Génie Télécommunications">Génie des Télécommunications (GT)</option>
                                <option value="Génie Informatique Industrielle">Génie Informatique Industrielle (GII)</option>
                                <option value="Ingénierie Données">Ingénierie des Données et Systèmes Décisionnels (IDSD)</option>
                            </optgroup>

                            <optgroup label="🎯 Mastère Professionnel ">
                                <option value="MP RITEL">MP Réseaux Informatiques et Télécommunications (RITEL)</option>
                                <option value="MP Systèmes Embarqués">MP Systèmes Embarqués (SE)</option>
                                <option value="MP Industriel">MP Vision Robotique et Systèmes Industriels (II)</option>
                                <option value="MP Auto-Aéro">MP Ingénierie Automobile et Aéronautique</option>
                            </optgroup>

                            <optgroup label="🔬 Mastère de Recherche ">
                                <option value="MR STIC">MR Technologies de l'Information et de la Communication (STIC)</option>
                                <option value="MR ISI">MR Ingénierie des Systèmes Informatiques (ISI)</option>
                            </optgroup>

                            <optgroup label="📖 Doctorat ">
                                <option value="Doctorat">Doctorat en Sciences et Technologies</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="niveau">Niveau *</label>
                        <select id="niveau" name="niveau" class="form-control">
                            <option value="">Sélectionner d'abord une spécialité...</option>
                        </select>
                    </div>
                </div>

                <!-- Champs enseignant -->
                <div id="enseignantFields" style="display:none;">
                    <div class="form-info">
                        <strong>👨‍🏫 Informations enseignant</strong>
                    </div>

                    <div class="form-group">
                        <label for="departement">Département *</label>
                        <select id="departement" name="departement" class="form-control">
                            <option value="">Sélectionner...</option>
                            <option value="Électronique">Département Électronique</option>
                            <option value="Télécommunications">Département Télécommunications</option>
                            <option value="Informatique Industrielle">Département Informatique Industrielle</option>
                            <option value="Mathématiques et Informatique Décisionnelle">Département Mathématiques et Informatique Décisionnelle</option>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="grade">Grade *</label>
                        <select id="grade" name="grade" class="form-control">
                            <option value="">Sélectionner...</option>
                            <option value="Assistant">Assistant</option>
                            <option value="Maître Assistant">Maître Assistant</option>
                            <option value="Maître de Conférences">Maître de Conférences</option>
                            <option value="Professeur">Professeur</option>
                            <option value="Professeur des Universités">Professeur des Universités</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" hidden>
                    <label for="annee_scolaire">Année scolaire</label>
                    <input type="text" id="annee_scolaire" name="annee_scolaire"
                        value="2024-2025" readonly class="form-control">
                </div>

                <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
            </form>

            <div class="register-footer">
                <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script>
        // Configuration des niveaux selon la spécialité
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

        // Basculer entre champs étudiant/enseignant
        function toggleFields() {
            const isStudent = document.querySelector('input[name="type_utilisateur"][value="etudiant"]').checked;
            const etudiantFields = document.getElementById('etudiantFields');
            const enseignantFields = document.getElementById('enseignantFields');

            const studentInputs = etudiantFields.querySelectorAll('input, select');
            const teacherInputs = enseignantFields.querySelectorAll('input, select');

            if (isStudent) {
                etudiantFields.style.display = 'block';
                enseignantFields.style.display = 'none';

                studentInputs.forEach(input => input.setAttribute('required', 'required'));
                teacherInputs.forEach(input => {
                    input.removeAttribute('required');
                    input.value = '';
                });
            } else {
                etudiantFields.style.display = 'none';
                enseignantFields.style.display = 'block';

                teacherInputs.forEach(input => input.setAttribute('required', 'required'));
                studentInputs.forEach(input => {
                    input.removeAttribute('required');
                    input.value = '';
                });
            }
        }

        // Validation avant soumission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;

            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }

            const type = document.querySelector('input[name="type_utilisateur"]:checked').value;

            if (type === 'etudiant') {
                const numero = document.getElementById('numero_etudiant').value.trim();
                const specialite = document.getElementById('specialite').value;
                const niveau = document.getElementById('niveau').value;

                if (!numero || !specialite || !niveau) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs obligatoires (numéro étudiant, spécialité et niveau).');
                    return false;
                }
            } else if (type === 'enseignant') {
                const departement = document.getElementById('departement').value;
                const grade = document.getElementById('grade').value;

                if (!departement || !grade) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs obligatoires (département et grade).');
                    return false;
                }
            }
        });

        // Initialiser au chargement
        document.addEventListener('DOMContentLoaded', function() {
            toggleFields();
            updateNiveaux();
        });
    </script>
    <!-- <script>
        function toggleFields() {
            const type = document.querySelector('input[name="type_utilisateur"]:checked').value;
            const etudiantFields = document.getElementById('etudiantFields');
            const enseignantFields = document.getElementById('enseignantFields');

            if (type === 'etudiant') {
                etudiantFields.style.display = 'block';
                enseignantFields.style.display = 'none';
            } else {
                etudiantFields.style.display = 'none';
                enseignantFields.style.display = 'block';
            }
        }
    </script> -->

    <!-- <script>
    function toggleFields() {
        const isStudent = document.querySelector('input[name="type_utilisateur"][value="etudiant"]').checked;
        const etudiantFields = document.getElementById('etudiantFields');
        const enseignantFields = document.getElementById('enseignantFields');

        // Student Fields
        const studentInputs = etudiantFields.querySelectorAll('input, select');
        // Teacher Fields
        const teacherInputs = enseignantFields.querySelectorAll('input, select');

        if (isStudent) {
            etudiantFields.style.display = 'block';
            enseignantFields.style.display = 'none';
            
            // Set required for student fields, remove for teacher fields
            studentInputs.forEach(input => input.setAttribute('required', 'required'));
            teacherInputs.forEach(input => input.removeAttribute('required'));
        } else {
            etudiantFields.style.display = 'none';
            enseignantFields.style.display = 'block';
            
            // Set required for teacher fields, remove for student fields
            teacherInputs.forEach(input => input.setAttribute('required', 'required'));
            studentInputs.forEach(input => input.removeAttribute('required'));
        }
    }

    // Call it once on page load to set the initial state (since 'etudiant' is checked by default)
    document.addEventListener('DOMContentLoaded', toggleFields);
</script> -->
</body>

</html>