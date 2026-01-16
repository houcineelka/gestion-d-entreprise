<?php
/**
 * Vue Dashboard - Tableau de bord
 * La logique métier est gérée par PageDashboardController
 */

$pageTitle = 'Tableau de Bord';

// Charger le contrôleur
require_once __DIR__ . '/../controllers/DashboardController.php';

// Initialiser et traiter la requête
$controller = new DashboardController();
$controller->handleRequest();

// Récupérer les données du contrôleur
$totalClients = $controller->getTotalClients();
$totalCommandes = $controller->getTotalCommandes();
$totalProduits = $controller->getTotalProduits();
$totalUtilisateurs = $controller->getTotalUtilisateurs();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="header">
    <h1>Tableau de Bord</h1>
    <p>Vue d'ensemble de l'activité</p>
</div>

<div class="stats">
    <div class="stat-card">
        <div class="stat-label">Total Clients</div>
        <div class="stat-value"><?php echo number_format($totalClients); ?></div>
        <div class="stat-change">Base de données</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Produits en Stock</div>
        <div class="stat-value"><?php echo number_format($totalProduits); ?></div>
        <div class="stat-change">Disponibles</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Commandes</div>
        <div class="stat-value"><?php echo number_format($totalCommandes); ?></div>
        <div class="stat-change">Total</div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Utilisateurs</div>
        <div class="stat-value"><?php echo number_format($totalUtilisateurs); ?></div>
        <div class="stat-change">Actifs</div>
    </div>
</div>

<div class="actions-section">
    <h2>Actions Rapides</h2>
    <div class="actions-grid">
        <?php if ($controller->canViewClients()): ?>
        <a href="clients.php?action=add" class="action-card">
            <div class="action-icon">+</div>
            <div>Nouveau Client</div>
        </a>
        <?php endif; ?>
        <?php if ($controller->canViewCommandes()): ?>
        <a href="commandes.php?action=add" class="action-card">
            <div class="action-icon">📝</div>
            <div>Nouvelle Commande</div>
        </a>
        <?php endif; ?>
        <?php if ($controller->canViewStock()): ?>
        <a href="produits.php" class="action-card">
            <div class="action-icon">📊</div>
            <div>Voir Stock</div>
        </a>
        <?php endif; ?>
        <?php if ($controller->canViewPrimes()): ?>
        <a href="primes.php" class="action-card">
            <div class="action-icon">💰</div>
            <div>Voir Primes</div>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
