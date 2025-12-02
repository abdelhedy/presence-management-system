<?php
session_start();
require_once '../../controllers/AuthController.php';
require_once '../../controllers/EnseignantController.php';
require_once '../../controllers/SeanceController.php';

AuthController::requireUserType('enseignant');

$enseignantController = new EnseignantController();
$seanceController = new SeanceController();
$idEnseignant = $_SESSION['enseignant_id'];

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

// Vérifier que la séance appartient à l'enseignant
if ($seance->id_enseignant != $idEnseignant) {
    header('Location: presences.php');
    exit;
}

// Récupérer les présences
$presencesResult = $enseignantController->consulterPresencesSeance($idSeance);
$presences = $presencesResult['success'] ? $presencesResult['presences'] : [];
$stats = $presencesResult['stats'] ?? [];

// Préparer le nom du fichier
$filename = 'presences_' . $seance->code_cours . '_' . date('Ymd', strtotime($seance->date_seance)) . '.xls';

// Headers pour téléchargement Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// BOM UTF-8 pour Excel
echo "\xEF\xBB\xBF";
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Liste de présence</x:Name>
                    <x:WorksheetOptions>
                        <x:Print>
                            <x:ValidPrinterInfo />
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
</head>

<body>
    <table border="1">
        <tr>
            <td colspan="6" style="background-color: #0891b2; color: white; font-weight: bold; font-size: 16px; text-align: center; padding: 10px;">
                LISTE DE PRÉSENCE
            </td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f1f5f9; font-weight: bold;">Cours :</td>
            <td colspan="4"><?= htmlspecialchars($seance->nom_cours) ?> (<?= htmlspecialchars($seance->code_cours) ?>)</td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f1f5f9; font-weight: bold;">Date :</td>
            <td colspan="4"><?= date('d/m/Y', strtotime($seance->date_seance)) ?></td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f1f5f9; font-weight: bold;">Horaire :</td>
            <td colspan="4"><?= substr($seance->heure_debut, 0, 5) ?> - <?= substr($seance->heure_fin, 0, 5) ?></td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f1f5f9; font-weight: bold;">Salle :</td>
            <td colspan="4"><?= htmlspecialchars($seance->salle) ?></td>
        </tr>
        <tr>
            <td colspan="6"></td>
        </tr>
        <tr style="background-color: #e2e8f0;">
            <td colspan="6" style="font-weight: bold; padding: 5px;">STATISTIQUES</td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f8fafc;">Total inscrits</td>
            <td colspan="4"><?= $stats['total'] ?? 0 ?></td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f8fafc;">Présents</td>
            <td colspan="4" style="color: #10b981; font-weight: bold;"><?= $stats['presents'] ?? 0 ?></td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f8fafc;">Absents</td>
            <td colspan="4" style="color: #ef4444; font-weight: bold;"><?= $stats['absents'] ?? 0 ?></td>
        </tr>
        <tr>
            <td colspan="2" style="background-color: #f8fafc;">Taux de présence</td>
            <td colspan="4" style="font-weight: bold;"><?= $stats['taux'] ?? 0 ?>%</td>
        </tr>
        <tr>
            <td colspan="6"></td>
        </tr>
        <tr style="background-color: #0891b2; color: white; font-weight: bold;">
            <td style="padding: 8px;">N°</td>
            <td style="padding: 8px;">Nom complet</td>
            <td style="padding: 8px;">Email</td>
            <td style="padding: 8px;">Statut</td>
            <td style="padding: 8px;">Heure de marquage</td>
            <td style="padding: 8px;">Score reconnaissance</td>
        </tr>
        <?php if (empty($presences)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">
                    Aucune donnée de présence disponible
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($presences as $index => $presence): ?>
                <tr style="<?= $index % 2 == 0 ? 'background-color: #f8fafc;' : '' ?>">
                    <td style="padding: 5px;"><?= $index + 1 ?></td>
                    <td style="padding: 5px;"><?= htmlspecialchars($presence['nom_complet']) ?></td>
                    <td style="padding: 5px;"><?= htmlspecialchars($presence['email']) ?></td>
                    <td style="padding: 5px; <?= $presence['statut'] === 'present' ? 'color: #10b981; font-weight: bold;' : 'color: #ef4444; font-weight: bold;' ?>">
                        <?= $presence['statut'] === 'present' ? '✓ Présent' : '✗ Absent' ?>
                    </td>
                    <td style="padding: 5px;">
                        <?= $presence['heure_marquage'] ? date('H:i:s', strtotime($presence['heure_marquage'])) : '-' ?>
                    </td>
                    <td style="padding: 5px;">
                        <?= $presence['score_reconnaissance'] ? round($presence['score_reconnaissance'] * 100, 1) . '%' : '-' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <tr>
            <td colspan="6"></td>
        </tr>
        <tr>
            <td colspan="6" style="text-align: center; color: #64748b; font-size: 11px; padding: 10px;">
                Document généré le <?= date('d/m/Y à H:i') ?> - Système de Gestion des Présences
            </td>
        </tr>
    </table>
</body>

</html>