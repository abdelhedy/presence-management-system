<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

AuthController::requireUserType('administrateur');

$adminController = new AdminController();

// Gérer la suppression
$message = "";
if (isset($_GET['delete']) && isset($_GET['confirm'])) {
    $userId = (int)$_GET['delete'];
    $result = $adminController->deleteUser($userId);
    $message = $result['success'] ?
        "<div class='alert alert-success'>{$result['message']}</div>" :
        "<div class='alert alert-error'>{$result['error']}</div>";
}

// Récupérer tous les utilisateurs
$usersResult = $adminController->getAllUsers();
$users = $usersResult['success'] ? $usersResult['users'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Utilisateurs - Admin</title>
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
        }

        .users-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .users-table tr:hover {
            background: var(--light-bg);
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-etudiant {
            background: rgba(8, 145, 178, 0.1);
            color: var(--primary-color);
        }

        .badge-enseignant {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .badge-administrateur {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-delete:hover {
            background: #dc2626;
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>⚙️ Administration</h2>
                <p style="font-size: 0.9rem; color: rgba(255,255,255,0.6);">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </p>
            </div>
            <a href="dashboard.php" class="nav-item"><span>📊</span> Dashboard</a>
            <a href="utilisateurs.php" class="nav-item active"><span>👥</span> Utilisateurs</a>
            <a href="ajouter_utilisateur.php" class="nav-item"><span>➕</span> Ajouter Utilisateur</a>
            <a href="cours.php" class="nav-item"><span>📚</span> Cours</a>
            <a href="seances.php" class="nav-item"><span>🗓️</span> Séances</a>
            <a href="presences.php" class="nav-item"><span>✓</span> Présences</a>
            <a href="../logout.php" class="nav-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;"><span>🚪</span> Déconnexion</a>
        </nav>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">👥 Gestion des Utilisateurs</h1>
                    <p style="color: var(--text-light);">Liste de tous les utilisateurs du système</p>
                </div>
                <a href="ajouter_utilisateur.php" class="btn btn-primary">
                    ➕ Ajouter un utilisateur
                </a>
            </div>

            <?= $message ?>

            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Téléphone</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem;">
                                    <p style="font-size: 2rem;">👤</p>
                                    <p style="color: var(--text-light);">Aucun utilisateur trouvé</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id_utilisateur'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $user['type_utilisateur'] ?>">
                                            <?= ucfirst($user['type_utilisateur']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user['numero_telephone'] ?? '-') ?></td>
                                    <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                                    <td>
                                        <?php if ($user['type_utilisateur'] !== 'administrateur' || $user['id_utilisateur'] != $_SESSION['user_id']): ?>
                                            <a href="?delete=<?= $user['id_utilisateur'] ?>"
                                                class="btn-delete"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer <?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?> ? Toutes ses données (cours, séances, inscriptions, présences) seront supprimées définitivement.')">
                                                🗑️ Supprimer
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--text-light); font-size: 0.9rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 2rem; padding: 1rem; background: rgba(8,145,178,0.1); border-radius: 8px;">
                <strong>ℹ️ Note importante :</strong>
                <ul style="margin-top: 0.5rem; padding-left: 1.5rem;">
                    <li>La suppression d'un <strong>étudiant</strong> supprimera toutes ses inscriptions, présences et images de référence</li>
                    <li>La suppression d'un <strong>enseignant</strong> supprimera tous ses cours, séances, inscriptions et présences associées</li>
                    <li>Vous ne pouvez pas supprimer votre propre compte administrateur</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Confirmer suppression avec param confirm
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm(this.getAttribute('data-confirm') || 'Êtes-vous sûr ?')) {
                    e.preventDefault();
                    return false;
                }
                // Ajouter le paramètre confirm
                this.href += '&confirm=1';
            });
        });
    </script>
</body>

</html>