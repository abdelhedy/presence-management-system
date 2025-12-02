<?php
session_start();
require_once '../controllers/AuthController.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $authController = new AuthController();
    $result = $authController->login($_POST['email'], $_POST['password']);

    if ($result['success']) {
        header("Location: " . $result['redirect']);
        exit();
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Système de Présence</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔐</div>
                <h2>Connexion</h2>
                <p>Connectez-vous à votre espace personnel</p>
            </div>

            <!-- Alert de démonstration -->
            <!-- <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                <strong>🎨 Interface Statique</strong><br>
                Ceci est la version statique pour tester le design.<br>
                Comptes de test :
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <li>admin@ens.tn / admin123</li>
                    <li>prof@ens.tn / prof123</li>
                    <li>etudiant@ens.tn / etud123</li>
                </ul>
            </div> -->

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">📧 Email</label>
                    <input type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="votre.email@exemple.com"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">🔒 Mot de passe</label>
                    <input type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        required>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" style="margin-right: 0.5rem;">
                        <span style="color: var(--text-light); font-size: 0.9rem;">Se souvenir de moi</span>
                    </label>
                    <a href="#" style="color: var(--primary-color); text-decoration: none; font-size: 0.9rem;">
                        Mot de passe oublié?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    🚀 Se connecter
                </button>
            </form>

            <div class="login-footer">
                <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
                <p><a href="index.php">← Retour à l'accueil</a></p>
            </div>
        </div>

        <!-- Info Box -->
        <!-- <div style="margin-top: 2rem; text-align: center;">
            <div style="background: rgba(255, 255, 255, 0.95); padding: 1.5rem; border-radius: 16px; 
                        box-shadow: 0 10px 40px rgba(0,0,0,0.1); max-width: 480px; margin: 0 auto;">
                <h3 style="color: var(--primary-color); margin-bottom: 1rem;">Accès par rôle</h3>
                <div style="display: grid; gap: 0.8rem; text-align: left;">
                    <div style="padding: 0.8rem; background: rgba(8, 145, 178, 0.05); border-radius: 8px; 
                                border-left: 4px solid var(--primary-color);">
                        <strong>👨‍🎓 Étudiant</strong>
                        <p style="margin: 0.3rem 0 0 0; color: var(--text-light); font-size: 0.9rem;">
                            Accès au marquage de présence et suivi personnel
                        </p>
                    </div>
                    <div style="padding: 0.8rem; background: rgba(16, 185, 129, 0.05); border-radius: 8px; 
                                border-left: 4px solid var(--success);">
                        <strong>👨‍🏫 Enseignant</strong>
                        <p style="margin: 0.3rem 0 0 0; color: var(--text-light); font-size: 0.9rem;">
                            Gestion des cours et consultation des présences
                        </p>
                    </div>
                    <div style="padding: 0.8rem; background: rgba(245, 158, 11, 0.05); border-radius: 8px; 
                                border-left: 4px solid var(--warning);">
                        <strong>⚙️ Administrateur</strong>
                        <p style="margin: 0.3rem 0 0 0; color: var(--text-light); font-size: 0.9rem;">
                            Supervision complète et gestion des utilisateurs
                        </p>
                    </div>
                </div>
            </div>
        </div> -->
    </div>

    <script>
        // Animation d'entrée
        window.addEventListener('load', () => {
            const loginBox = document.querySelector('.login-box');
            loginBox.style.opacity = '0';
            loginBox.style.transform = 'translateY(-20px)';

            setTimeout(() => {
                loginBox.style.transition = 'all 0.5s ease';
                loginBox.style.opacity = '1';
                loginBox.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>

</html>