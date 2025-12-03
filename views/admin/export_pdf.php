<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';
require_once '../../controllers/SeanceController.php';

AuthController::requireUserType('administrateur');

$adminController = new AdminController();
$seanceController = new SeanceController();

// Récupérer l'ID de la séance
if (!isset($_GET['id_seance']) || !is_numeric($_GET['id_seance'])) {
    header('Location: presences.php');
    exit;
}

$idSeance = intval($_GET['id_seance']);

// Récupérer les informations de la séance
$seanceResult = $seanceController->getById($idSeance);
if (!$seanceResult['success']) {
    header('Location: presences.php');
    exit;
}
$seance = $seanceResult['seance'];

// Récupérer les présences
$presencesResult = $adminController->consulterPresencesSeance($idSeance);
$presences = $presencesResult['success'] ? $presencesResult['presences'] : [];
$stats = $presencesResult['stats'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste de présence - <?= htmlspecialchars($seance->nom_cours) ?></title>
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            color: #333;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #0891b2;
            padding-bottom: 15px;
        }

        .header h1 {
            margin: 0;
            color: #0891b2;
            font-size: 24px;
            font-weight: bold;
        }

        .info-section {
            margin-bottom: 25px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #0891b2;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .info-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .info-label {
            font-weight: 600;
            color: #475569;
            min-width: 140px;
        }

        .info-value {
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        th {
            background: #0891b2;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-present {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-absent {
            background: #fee2e2;
            color: #991b1b;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #0891b2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .print-button:hover {
            background: #0e7490;
        }

        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>

<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimer / Sauvegarder en PDF</button>

    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 8px;">
        <div>
            <div style="font-size: 13px; color: #64748b; margin-bottom: 3px;">Administrateur</div>
            <div style="font-size: 16px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 3px;">Date de génération</div>
            <div style="font-size: 14px; color: #1e293b;"><?= date('d/m/Y à H:i') ?></div>
        </div>
    </div>

    <div class="header">
        <h1>📋 Liste de Présence</h1>
        <div style="margin-top: 10px; font-size: 16px; color: #1e293b;">
            <strong><?= htmlspecialchars($seance->nom_cours) ?></strong> (<?= htmlspecialchars($seance->code_cours) ?>)
        </div>
    </div>

    <div class="info-section">
        <div class="info-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="info-row">
                <span class="info-label">📅 Date :</span>
                <span class="info-value"><?= date('d/m/Y', strtotime($seance->date_seance)) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">🕐 Horaire :</span>
                <span class="info-value"><?= substr($seance->heure_debut, 0, 5) ?> - <?= substr($seance->heure_fin, 0, 5) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">🏫 Salle :</span>
                <span class="info-value"><?= htmlspecialchars($seance->salle) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">👥 Total inscrits :</span>
                <span class="info-value" style="font-weight: bold;"><?= $stats['total'] ?? 0 ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">✓ Présents :</span>
                <span class="info-value" style="font-weight: bold; color: #10b981;"><?= $stats['presents'] ?? 0 ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">✗ Absents :</span>
                <span class="info-value" style="font-weight: bold; color: #ef4444;"><?= $stats['absents'] ?? 0 ?></span>
            </div>
            <div class="info-row" style="grid-column: span 3; border-top: 1px solid #e2e8f0; padding-top: 12px; margin-top: 8px;">
                <span class="info-label">📊 Taux de présence :</span>
                <span class="info-value" style="font-weight: bold; color: #0891b2; font-size: 18px;"><?= $stats['taux'] ?? 0 ?>%</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 50px;">N°</th>
                <th>Nom complet</th>
                <th>Email</th>
                <th style="width: 100px;">Statut</th>
                <th style="width: 120px;">Heure de marquage</th>
                <th style="width: 100px;">Méthode</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($presences)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
                        Aucune donnée de présence disponible
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($presences as $index => $presence): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($presence['nom_complet']) ?></td>
                        <td><?= htmlspecialchars($presence['email']) ?></td>
                        <td>
                            <?php if ($presence['statut'] === 'present'): ?>
                                <span class="badge badge-present">✓ Présent</span>
                            <?php else: ?>
                                <span class="badge badge-absent">✗ Absent</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $presence['heure_marquage'] ? date('H:i:s', strtotime($presence['heure_marquage'])) : '-' ?>
                        </td>
                        <td>
                            <?= $presence['methode_validation'] ? ucfirst($presence['methode_validation']) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Système de Gestion des Présences - Document généré le <?= date('d/m/Y à H:i') ?></p>
    </div>
</body>

</html>