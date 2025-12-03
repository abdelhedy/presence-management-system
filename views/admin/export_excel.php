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
    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse;">
        <!-- En-tête principal -->
        <tr>
            <td colspan="6" align="center" style="background-color: #0891b2; color: white; font-weight: bold; font-size: 16px; padding: 10px;">
                LISTE DE PRESENCE
            </td>
        </tr>
        <tr>
            <td colspan="6">&nbsp;</td>
        </tr>

        <!-- Informations du cours -->
        <tr>
            <td colspan="6" style="background-color: #cccccc; font-weight: bold; padding: 5px;">INFORMATIONS DU COURS</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Cours :</td>
            <td colspan="5"><?= htmlspecialchars($seance->nom_cours) ?> (<?= htmlspecialchars($seance->code_cours) ?>)</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Date :</td>
            <td colspan="5"><?= date('d/m/Y', strtotime($seance->date_seance)) ?></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Horaire :</td>
            <td colspan="5"><?= substr($seance->heure_debut, 0, 5) ?> - <?= substr($seance->heure_fin, 0, 5) ?></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Salle :</td>
            <td colspan="5"><?= htmlspecialchars($seance->salle) ?></td>
        </tr>
        <tr>
            <td colspan="6">&nbsp;</td>
        </tr>

        <!-- Statistiques -->
        <tr>
            <td colspan="6" style="background-color: #cccccc; font-weight: bold; padding: 5px;">STATISTIQUES</td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Total inscrits</td>
            <td colspan="4"><?= $stats['total'] ?? 0 ?></td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Presents</td>
            <td colspan="4"><?= $stats['presents'] ?? 0 ?></td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Absents</td>
            <td colspan="4"><?= $stats['absents'] ?? 0 ?></td>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold;">Taux de presence</td>
            <td colspan="4"><?= $stats['taux'] ?? 0 ?>%</td>
        </tr>
        <tr>
            <td colspan="6">&nbsp;</td>
        </tr>

        <!-- Liste des étudiants -->
        <tr>
            <td colspan="6" style="background-color: #cccccc; font-weight: bold; padding: 5px;">LISTE DES ETUDIANTS</td>
        </tr>
        <tr style="font-weight: bold;">
            <td align="center">N°</td>
            <td>Nom complet</td>
            <td>Email</td>
            <td align="center">Statut</td>
            <td align="center">Heure</td>
            <td align="center">Methode</td>
        </tr>
        <?php if (empty($presences)): ?>
            <tr>
                <td colspan="6" align="center" style="padding: 20px;">
                    Aucune donnee de presence disponible
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($presences as $index => $presence): ?>
                <tr>
                    <td align="center"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($presence['nom_complet']) ?></td>
                    <td><?= htmlspecialchars($presence['email']) ?></td>
                    <td align="center" style="font-weight: bold;">
                        <?= $presence['statut'] === 'present' ? 'Present' : 'Absent' ?>
                    </td>
                    <td align="center">
                        <?= $presence['heure_marquage'] ? date('H:i:s', strtotime($presence['heure_marquage'])) : '-' ?>
                    </td>
                    <td align="center">
                        <?php
                        $methode = $presence['methode_validation'] ?? '';
                        if ($methode === 'reconnaissance_faciale') {
                            echo 'Faciale';
                        } elseif ($methode === 'manuel') {
                            echo 'Manuelle';
                        } elseif ($methode === 'automatique') {
                            echo 'Automatique';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Pied de page -->
        <tr>
            <td colspan="6">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="6" align="center" style="font-size: 10px; padding: 10px;">
                Document genere le <?= date('d/m/Y à H:i') ?> par <?= htmlspecialchars($_SESSION['user_name']) ?><br />
                Systeme de Gestion des Presences
            </td>
        </tr>
    </table>
</body>

</html>