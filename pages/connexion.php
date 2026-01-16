<?php
session_start();

// Si déjà connecté, redirection vers dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Récupérer l'erreur depuis l'URL si présente
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface A - Connexion</title>
    <link rel="stylesheet" href="./styles/connexion.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>DistribuMax</h1>
            <p>Système de Gestion</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="../controllers/AuthController.php?action=login">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="votre.email@entreprise.com" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="remember-forgot">
                <div class="spacer"></div>
                <a href="#" class="forgot-password">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn-login">Se Connecter</button>
        </form>

        <div class="register-link">
            Pas de compte ? <a href="#">Créer un compte</a>
        </div>
    </div>
</body>
</html>