<?php
session_start();

// Vérifier l'authentification et les droits
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Manager', 'RH'])) {
    die('Accès non autorisé');
}

require_once __DIR__ . '/../services/BusinessService.php';

$businessService = new BusinessService();
$annee = $_GET['annee'] ?? date('Y');

$rapportHTML = $businessService->genererRapportPrimesPDF($annee);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des Primes - <?php echo $annee; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #7f8c8d;
            font-size: 12px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Imprimer / Sauvegarder en PDF</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #95a5a6; color: white; border: none; border-radius: 4px; cursor: pointer;">Fermer</button>
    </div>

    <?php echo $rapportHTML; ?>

    <div class="footer">
        <p>Rapport généré le <?php echo date('d/m/Y à H:i'); ?> par <?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></p>
        <p>DistribuMax - Système de Gestion de Grande Distribution</p>
    </div>
</body>
</html>
