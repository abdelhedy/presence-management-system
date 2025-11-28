<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['type_utilisateur'] !== 'enseignant') {
    header('Location: ../../index.php');
    exit();
}

// Récupérer les données pour les sélections
$database = new Database();
$db = $database->getConnection();

// Récupérer les niveaux, spécialités et années distincts depuis la table etudiants
$query_niveaux = "SELECT DISTINCT niveau FROM systeme_presence.etudiants ORDER BY niveau";
$stmt_niveaux = $db->prepare($query_niveaux);
$stmt_niveaux->execute();
$niveaux = $stmt_niveaux->fetchAll(PDO::FETCH_COLUMN);

$query_specialites = "SELECT DISTINCT specialite FROM systeme_presence.etudiants ORDER BY specialite";
$stmt_specialites = $db->prepare($query_specialites);
$stmt_specialites->execute();
$specialites = $stmt_specialites->fetchAll(PDO::FETCH_COLUMN);

$query_annees = "SELECT DISTINCT annee_scolaire FROM systeme_presence.etudiants ORDER BY annee_scolaire DESC";
$stmt_annees = $db->prepare($query_annees);
$stmt_annees->execute();
$annees = $stmt_annees->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Cours - Système de Présence</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> Présence Pro</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i> Tableau de bord
                </a>
                <a href="cours.php" class="nav-item">
                    <i class="fas fa-book"></i> Mes Cours
                </a>
                <a href="create_cours.php" class="nav-item active">
                    <i class="fas fa-plus-circle"></i> Créer un Cours
                </a>
                <a href="seances.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i> Séances
                </a>
                <a href="presences.php" class="nav-item">
                    <i class="fas fa-clipboard-check"></i> Présences
                </a>
                <a href="../../controllers/AuthController.php?action=logout" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <h1><i class="fas fa-plus-circle"></i> Créer un nouveau cours</h1>
                <a href="cours.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </header>

            <div class="form-container">
                <div id="message" class="message" style="display: none;"></div>
                
                <form id="createCoursForm" class="form-card">
                    <div class="form-section">
                        <h3><i class="fas fa-info-circle"></i> Informations du cours</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom_cours">
                                    <i class="fas fa-book"></i> Nom du cours *
                                </label>
                                <input type="text" id="nom_cours" name="nom_cours" required 
                                       placeholder="Ex: Développement Web Avancé">
                            </div>

                            <div class="form-group">
                                <label for="code_cours">
                                    <i class="fas fa-code"></i> Code du cours *
                                </label>
                                <input type="text" id="code_cours" name="code_cours" required 
                                       placeholder="Ex: DEV301">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">
                                <i class="fas fa-align-left"></i> Description
                            </label>
                            <textarea id="description" name="description" rows="4" 
                                      placeholder="Description détaillée du cours..."></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-users"></i> Public cible</h3>
                        <p class="form-help">
                            <i class="fas fa-info-circle"></i> 
                            Les étudiants correspondant à ces critères seront automatiquement inscrits
                        </p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="niveau">
                                    <i class="fas fa-layer-group"></i> Niveau *
                                </label>
                                <select id="niveau" name="niveau" required>
                                    <option value="">Sélectionner un niveau</option>
                                    <?php foreach ($niveaux as $niveau): ?>
                                        <option value="<?php echo htmlspecialchars($niveau); ?>">
                                            <?php echo htmlspecialchars($niveau); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="specialite">
                                    <i class="fas fa-book-open"></i> Spécialité *
                                </label>
                                <select id="specialite" name="specialite" required>
                                    <option value="">Sélectionner une spécialité</option>
                                    <?php foreach ($specialites as $specialite): ?>
                                        <option value="<?php echo htmlspecialchars($specialite); ?>">
                                            <?php echo htmlspecialchars($specialite); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="annee_scolaire">
                                    <i class="fas fa-calendar"></i> Année scolaire *
                                </label>
                                <select id="annee_scolaire" name="annee_scolaire" required>
                                    <option value="">Sélectionner une année</option>
                                    <?php foreach ($annees as $annee): ?>
                                        <option value="<?php echo htmlspecialchars($annee); ?>">
                                            <?php echo htmlspecialchars($annee); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="etudiantsCount" class="info-box" style="display: none;">
                            <i class="fas fa-users"></i>
                            <span id="countText">Chargement...</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="window.location.href='cours.php'" class="btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-check"></i> Créer le cours
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    // Fonction pour compter les étudiants correspondants
    function updateEtudiantsCount() {
        const niveau = document.getElementById('niveau').value;
        const specialite = document.getElementById('specialite').value;
        const annee = document.getElementById('annee_scolaire').value;
        
        if (niveau && specialite && annee) {
            fetch('../../controllers/CoursController.php?action=countEtudiants', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    niveau: niveau,
                    specialite: specialite,
                    annee_scolaire: annee
                })
            })
            .then(response => response.json())
            .then(data => {
                const countBox = document.getElementById('etudiantsCount');
                const countText = document.getElementById('countText');
                
                if (data.success) {
                    countBox.style.display = 'flex';
                    countText.textContent = `${data.count} étudiant(s) sera/seront automatiquement inscrit(s) à ce cours`;
                    countBox.className = 'info-box info-success';
                } else {
                    countBox.style.display = 'flex';
                    countText.textContent = 'Aucun étudiant trouvé pour ces critères';
                    countBox.className = 'info-box info-warning';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        } else {
            document.getElementById('etudiantsCount').style.display = 'none';
        }
    }

    // Écouter les changements sur les champs
    document.getElementById('niveau').addEventListener('change', updateEtudiantsCount);
    document.getElementById('specialite').addEventListener('change', updateEtudiantsCount);
    document.getElementById('annee_scolaire').addEventListener('change', updateEtudiantsCount);

    // Soumettre le formulaire
    document.getElementById('createCoursForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const messageDiv = document.getElementById('message');
        
        // Ajouter l'ID de l'enseignant
        formData.append('id_enseignant', '<?php echo $_SESSION['user_id']; ?>');
        
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
                    <br><small>${data.inscriptions_count} étudiant(s) inscrit(s) automatiquement</small>
                `;
                
                // Rediriger après 2 secondes
                setTimeout(() => {
                    window.location.href = 'cours_detail.php?id=' + data.cours_id;
                }, 2000);
            } else {
                messageDiv.className = 'message message-error';
                messageDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message}`;
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