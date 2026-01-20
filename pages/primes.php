<?php
/**
 * Vue Primes - Affichage de la gestion des primes et remises
 * La logique métier est gérée par PagePrimeController
 */

$pageTitle = 'Gestion des Primes';
$additionalCss = './styles/primes.css';

// Charger le contrôleur
require_once __DIR__ . '/../controllers/PrimeController.php';

// Initialiser le contrôleur
$controller = new PrimeController();

// Vérifier si c'est une demande de téléchargement PDF
if (isset($_GET['action']) && $_GET['action'] === 'pdf') {
    $annee = $_GET['annee'] ?? date('Y');
    $controller->genererRapportPrimes($annee);
    exit;
}

// Traiter la requête normale
$controller->handleRequest();

// Récupérer les données du contrôleur
$message = $controller->getMessage();
$error = $controller->getError();
$annee = $controller->getAnnee();
$listePrimes = $controller->getListePrimes();
$listeClients = $controller->getListeClients();
$clientsAvecAchats = $controller->getClientsAvecAchats();
$totauxPrimes = $controller->getTotauxPrimes();
$totauxRemises = $controller->getTotauxRemises();

require_once __DIR__ . '/../includes/header.php';

// Vérifier que l'utilisateur a le droit d'accès
if (!$controller->hasAccess()) {
    echo '<div class="alert alert-error">Accès non autorisé. Cette page est réservée aux RH et Managers.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- En-tête de la page -->
<div class="header">
    <h1>Gestion des Primes et Remises</h1>
    <div class="header-buttons">
        <a href="primes.php?action=pdf&annee=<?php echo $annee; ?>" class="btn btn-primary">
            Télécharger Rapport PDF
        </a>
    </div>
</div>

<!-- Toolbar avec sélecteur d'année et calcul -->
<div class="toolbar">
    <form method="GET" action="" class="search-box">
        <label for="annee">Année :</label>
        <select id="annee" name="annee" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                <option value="<?php echo $y; ?>" <?php echo ($y == $annee) ? 'selected' : ''; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
        </select>
    </form>

    <form method="POST" action="" class="calcul-form">
        <input type="hidden" name="action" value="calculer">
        <input type="hidden" name="annee" value="<?php echo $annee; ?>">
        <button type="submit" class="btn btn-success">
            Calculer les Primes <?php echo $annee; ?>
        </button>
    </form>
</div>

<!-- Section Primes des Commerciaux -->
<div class="section-title">
    <h2>Primes des Commerciaux (10% du CA) - <?php echo $annee; ?></h2>
</div>

<div class="table-container">
    <?php if (count($listePrimes) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Commercial</th>
                <th>Email</th>
                <th>Chiffre d'Affaire</th>
                <th>Taux</th>
                <th>Montant Prime</th>
                <th>Date de Calcul</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($listePrimes as $prime): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($prime['prenom'] . ' ' . $prime['nom']); ?></strong>
                </td>
                <td><?php echo htmlspecialchars($prime['email']); ?></td>
                <td><?php echo number_format($prime['chiffre_affaire'], 2, ',', ' '); ?> €</td>
                <td>
                    <span class="badge badge-info"><?php echo number_format($prime['taux_prime'], 1); ?>%</span>
                </td>
                <td>
                    <span class="montant-prime"><?php echo number_format($prime['montant_prime'], 2, ',', ' '); ?> €</span>
                </td>
                <td><?php echo date('d/m/Y H:i', strtotime($prime['date_calcul'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2"><strong>TOTAL</strong></td>
                <td><strong><?php echo number_format($totauxPrimes['total_ca'], 2, ',', ' '); ?> €</strong></td>
                <td>-</td>
                <td><strong class="montant-prime"><?php echo number_format($totauxPrimes['total_primes'], 2, ',', ' '); ?> €</strong></td>
                <td>-</td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>Aucune prime calculée pour l'année <?php echo $annee; ?>.</p>
        <p>Cliquez sur "Calculer les Primes" pour générer les primes des commerciaux.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Section Remises Clients -->
<div class="section-title">
    <h2>Remises des Clients (2,5% des achats) - <?php echo $annee; ?></h2>
</div>

<div class="table-container">
    <?php if (count($clientsAvecAchats) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>Email</th>
                <th>Nb Commandes</th>
                <th>Total Achats</th>
                <th>Remise (2,5%)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientsAvecAchats as $client): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></strong>
                </td>
                <td><?php echo htmlspecialchars($client['email']); ?></td>
                <td>
                    <span class="badge badge-secondary"><?php echo $client['nb_commandes']; ?></span>
                </td>
                <td><?php echo number_format($client['total_achats'], 2, ',', ' '); ?> €</td>
                <td>
                    <span class="montant-remise"><?php echo number_format($client['remise'], 2, ',', ' '); ?> €</span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3"><strong>TOTAL</strong></td>
                <td><strong><?php echo number_format($totauxRemises['total_achats'], 2, ',', ' '); ?> €</strong></td>
                <td><strong class="montant-remise"><?php echo number_format($totauxRemises['total_remises'], 2, ',', ' '); ?> €</strong></td>
            </tr>
        </tfoot>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <p>Aucun client avec des achats pour l'année <?php echo $annee; ?>.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Statistiques rapides -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <span class="stat-value"><?php echo count($listePrimes); ?></span>
            <span class="stat-label">Commerciaux</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <span class="stat-value"><?php echo number_format($totauxPrimes['total_primes'], 0, ',', ' '); ?> €</span>
            <span class="stat-label">Total Primes</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🛍️</div>
        <div class="stat-info">
            <span class="stat-value"><?php echo count($clientsAvecAchats); ?></span>
            <span class="stat-label">Clients Actifs</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🎁</div>
        <div class="stat-info">
            <span class="stat-value"><?php echo number_format($totauxRemises['total_remises'], 0, ',', ' '); ?> €</span>
            <span class="stat-label">Total Remises</span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
