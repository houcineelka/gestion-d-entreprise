<?php
/**
 * Vue Utilisateurs - Affichage de la gestion des utilisateurs
 * La logique métier est gérée par PageUserController
 */

$pageTitle = 'Gestion des Utilisateurs';
$additionalCss = './styles/utilisateurs.css';

// Charger le contrôleur
require_once __DIR__ . '/../controllers/UserController.php';

// Initialiser et traiter la requête
$controller = new UserController();
$controller->handleRequest();

// Récupérer les données du contrôleur
$message = $controller->getMessage();
$error = $controller->getError();
$action = $controller->getAction();
$userData = $controller->getUserData();
$utilisateurs = $controller->getUtilisateurs();
$categories = $controller->getCategories();
$statistiques = $controller->getStatistiques();
$search = $controller->getSearch();
$filterRole = $controller->getFilterRole();

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

<?php if ($action === 'list'): ?>
    <!-- Liste des utilisateurs -->
    <div class="header">
        <h1>Gestion des Utilisateurs</h1>
        <div class="header-buttons">
            <a href="utilisateurs.php?action=add" class="btn-primary">+ Nouvel Utilisateur</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-label">Total Utilisateurs</div>
            <div class="stat-value"><?php echo $statistiques['total_users']; ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Actifs</div>
            <div class="stat-value"><?php echo $statistiques['active_users']; ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Managers</div>
            <div class="stat-value"><?php echo $statistiques['manager_count']; ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-label">Inactifs</div>
            <div class="stat-value"><?php echo $statistiques['inactive_users']; ?></div>
        </div>
    </div>

    <div class="toolbar">
        <form method="GET" action="" class="search-box">
            <input type="text" name="search" placeholder="Rechercher un utilisateur..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="role-btn">Rechercher</button>
        </form>
        <div class="role-filter">
            <a href="utilisateurs.php" class="role-btn <?php echo empty($filterRole) ? 'active' : ''; ?>">Tous</a>
            <a href="utilisateurs.php?role=Manager" class="role-btn <?php echo $filterRole === 'Manager' ? 'active' : ''; ?>">Manager</a>
            <a href="utilisateurs.php?role=Commercial" class="role-btn <?php echo $filterRole === 'Commercial' ? 'active' : ''; ?>">Commercial</a>
            <a href="utilisateurs.php?role=RH" class="role-btn <?php echo $filterRole === 'RH' ? 'active' : ''; ?>">RH</a>
            <a href="utilisateurs.php?role=Magasinier" class="role-btn <?php echo $filterRole === 'Magasinier' ? 'active' : ''; ?>">Magasinier</a>
        </div>
    </div>

    <div class="users-grid">
        <?php if (empty($utilisateurs)): ?>
            <div class="alert alert-info">Aucun utilisateur trouvé.</div>
        <?php else: ?>
            <?php foreach ($utilisateurs as $user): ?>
                <?php $permissions = UserController::getPermissions($user['categorie_nom']); ?>
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-avatar"><?php echo UserController::getInitials($user['prenom'], $user['nom']); ?></div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h3>
                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            <span class="role-badge <?php echo UserController::getRoleBadgeClass($user['categorie_nom']); ?>">
                                <?php echo htmlspecialchars($user['categorie_nom']); ?>
                            </span>
                            <span class="status-badge <?php echo $user['actif'] ? 'active' : 'inactive'; ?>">
                                <?php echo $user['actif'] ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="permissions">
                        <div class="permissions-title">Droits d'Accès</div>
                        <div class="permissions-list">
                            <?php foreach ($permissions as $perm): ?>
                                <div class="permission-item <?php echo $perm['granted'] ? 'granted' : 'denied'; ?>">
                                    <?php echo $perm['granted'] ? '✓' : '✗'; ?> <?php echo $perm['name']; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="user-actions">
                        <a href="utilisateurs.php?action=edit&id=<?php echo $user['id']; ?>" class="action-btn">Modifier</a>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="" style="flex: 1;" onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver cet utilisateur?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="action-btn" style="width: 100%;">Désactiver</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'add'): ?>
    <!-- Formulaire d'ajout -->
    <div class="header">
        <h1>Nouvel Utilisateur</h1>
        <a href="utilisateurs.php" class="btn-secondary">Retour à la liste</a>
    </div>

    <div class="form-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" required>
            </div>

            <div class="form-group">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe *</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="6">
                <small>Minimum 6 caractères</small>
            </div>

            <div class="form-group">
                <label for="categorie_id">Catégorie *</label>
                <select id="categorie_id" name="categorie_id" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>">
                        <?php echo htmlspecialchars($cat['nom']); ?> - <?php echo htmlspecialchars($cat['description'] ?? ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-primary">Créer l'utilisateur</button>
                <a href="utilisateurs.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

<?php elseif ($action === 'edit' && $userData): ?>
    <!-- Formulaire de modification -->
    <div class="header">
        <h1>Modifier l'Utilisateur</h1>
        <a href="utilisateurs.php" class="btn-secondary">Retour à la liste</a>
    </div>

    <div class="form-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $userData['id']; ?>">

            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($userData['nom']); ?>" required>
            </div>

            <div class="form-group">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($userData['prenom']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="categorie_id">Catégorie *</label>
                <select id="categorie_id" name="categorie_id" required>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $userData['categorie_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nom']); ?> - <?php echo htmlspecialchars($cat['description'] ?? ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="actif">Statut</label>
                <select id="actif" name="actif">
                    <option value="1" <?php echo $userData['actif'] ? 'selected' : ''; ?>>Actif</option>
                    <option value="0" <?php echo !$userData['actif'] ? 'selected' : ''; ?>>Inactif</option>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                <a href="utilisateurs.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
