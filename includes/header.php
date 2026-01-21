<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

// Déterminer la page active
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>DistribuMax</title>
    <link rel="stylesheet" href="./styles/dashboard.css">
    <?php if (isset($additionalCss)): ?>
    <link rel="stylesheet" href="<?php echo $additionalCss; ?>">
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <h2>DistribuMax</h2>
                <p>Gestion d'Entreprise</p>
            </div>

            <nav>
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <a href="dashboard.php" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">Tableau de bord</a>
                </div>

                <div class="nav-section">
                    <?php if (in_array($_SESSION['user_role'] ?? '', ['Manager', 'Commercial'])): ?>
                    <div class="nav-section-title">Gestion</div>
                    <a href="clients.php" class="nav-item <?php echo $currentPage === 'clients' ? 'active' : ''; ?>">Clients</a>
                    <a href="commandes.php" class="nav-item <?php echo $currentPage === 'commandes' ? 'active' : ''; ?>">Commandes</a>
                    <?php endif; ?>
                    <?php if (in_array($_SESSION['user_role'] ?? '', ['Manager', 'Magasinier'])): ?>
                    <a href="produits.php" class="nav-item <?php echo $currentPage === 'produits' ? 'active' : ''; ?>">Stock</a>
                    <?php endif; ?>
                </div>

                <?php if (in_array($_SESSION['user_role'] ?? '', ['Manager', 'RH'])): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Administration</div>
                    <a href="utilisateurs.php" class="nav-item <?php echo $currentPage === 'utilisateurs' ? 'active' : ''; ?>">Utilisateurs</a>
                    <a href="primes.php" class="nav-item <?php echo $currentPage === 'primes' ? 'active' : ''; ?>">Primes</a>
                    <a href="historique.php" class="nav-item <?php echo $currentPage === 'historique' ? 'active' : ''; ?>">Historique</a>
                </div>
                <?php endif; ?>
            </nav>

            <div class="user-info">
                <div class="name"><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></div>
                <div class="role"><?php echo htmlspecialchars($_SESSION['user_role']); ?></div>
                <a href="logout.php" class="btn-logout">Déconnexion</a>
            </div>
        </aside>

        <main class="main-content">