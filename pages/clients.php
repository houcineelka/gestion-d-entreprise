<?php
/**
 * Vue Clients - Affichage de la gestion des clients
 * La logique métier est gérée par PageClientController
 */

$pageTitle = 'Gestion des Clients';
$additionalCss = './styles/clients.css';

// Charger le contrôleur
require_once __DIR__ . '/../controllers/ClientController.php';

// Initialiser et traiter la requête
$controller = new ClientController();
$controller->handleRequest();

// Récupérer les données du contrôleur
$message = $controller->getMessage();
$error = $controller->getError();
$action = $controller->getAction();
$clientData = $controller->getClientData();
$clients = $controller->getClients();
$commerciaux = $controller->getCommerciaux();
$search = $controller->getSearch();

require_once __DIR__ . '/../includes/header.php';

// Vérifier que l'utilisateur a le droit d'accès
if (!$controller->hasAccess()) {
    echo '<div class="alert alert-error">Accès non autorisé. Cette page est réservée aux Managers et Commerciaux.</div>';
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

    <!-- Liste des clients -->
    <div class="header">
        <h1>Gestion des Clients</h1>
        <div class="header-buttons">
            <a href="clients.php?action=add" class="btn-primary">+ Ajouter un Client</a>
        </div>
    </div>

    <div class="toolbar">
        <form method="GET" action="" class="search-box">
            <input type="text" name="search" placeholder="Rechercher par nom, email, ville..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="filter-btn">Rechercher</button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Ville</th>
                    <th>Commandes</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #666;">Aucun client trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <div class="client-name"><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></div>
                                <div class="client-id">ID: #CL-<?php echo htmlspecialchars($client['id']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td><?php echo htmlspecialchars($client['telephone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($client['ville'] ?? '-'); ?></td>
                            <td><?php echo $controller->getCommandesCount($client['id']); ?></td>
                            <td><span class="badge active">Actif</span></td>
                            <td>
                                <div class="actions">
                                    <a href="clients.php?action=view&id=<?php echo $client['id']; ?>" class="btn-icon" title="Voir">👁️</a>
                                    <a href="clients.php?action=edit&id=<?php echo $client['id']; ?>" class="btn-icon" title="Modifier">✏️</a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $client['id']; ?>">
                                        <button type="submit" class="btn-icon" title="Supprimer">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <div class="pagination-info">
                Affichage de <?php echo count($clients); ?> clients
            </div>
        </div>
    </div>

<?php elseif ($action === 'add'): ?>
    <!-- Formulaire d'ajout -->
    <div class="header">
        <h1>Ajouter un Client</h1>
        <a href="clients.php" class="btn-secondary">Retour à la liste</a>
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
                <label for="telephone">Téléphone</label>
                <input type="text" id="telephone" name="telephone">
            </div>

            <div class="form-group">
                <label for="adresse">Adresse</label>
                <textarea id="adresse" name="adresse" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="ville">Ville</label>
                <input type="text" id="ville" name="ville">
            </div>

            <div class="form-group">
                <label for="code_postal">Code Postal</label>
                <input type="text" id="code_postal" name="code_postal">
            </div>

            <div class="form-group">
                <label for="commercial_id">Commercial assigné</label>
                <select id="commercial_id" name="commercial_id">
                    <option value="<?php echo $_SESSION['user_id']; ?>">Moi-même</option>
                    <?php foreach ($commerciaux as $commercial): ?>
                        <?php if ($commercial['id'] != $_SESSION['user_id']): ?>
                        <option value="<?php echo $commercial['id']; ?>">
                            <?php echo htmlspecialchars($commercial['prenom'] . ' ' . $commercial['nom']); ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-primary">Créer le client</button>
                <a href="clients.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

<?php elseif ($action === 'edit' && $clientData): ?>
    <!-- Formulaire de modification -->
    <div class="header">
        <h1>Modifier le Client</h1>
        <a href="clients.php" class="btn-secondary">Retour à la liste</a>
    </div>

    <div class="form-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $clientData['id']; ?>">

            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($clientData['nom']); ?>" required>
            </div>

            <div class="form-group">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($clientData['prenom']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($clientData['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="text" id="telephone" name="telephone" value="<?php echo htmlspecialchars($clientData['telephone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="adresse">Adresse</label>
                <textarea id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($clientData['adresse'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="ville">Ville</label>
                <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($clientData['ville'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="code_postal">Code Postal</label>
                <input type="text" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($clientData['code_postal'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="commercial_id">Commercial assigné</label>
                <select id="commercial_id" name="commercial_id">
                    <?php foreach ($commerciaux as $commercial): ?>
                        <option value="<?php echo $commercial['id']; ?>" <?php echo ($clientData['commercial_id'] == $commercial['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($commercial['prenom'] . ' ' . $commercial['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                <a href="clients.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

<?php elseif ($action === 'view' && $clientData): ?>
    <!-- Fiche client -->
    <div class="header">
        <h1>Fiche Client</h1>
        <div class="header-buttons">
            <a href="clients.php" class="btn-secondary">Retour à la liste</a>
            <a href="clients.php?action=edit&id=<?php echo $clientData['id']; ?>" class="btn-primary">Modifier</a>
        </div>
    </div>

    <div class="client-card">
        <h2><?php echo htmlspecialchars($clientData['prenom'] . ' ' . $clientData['nom']); ?></h2>

        <div class="client-info">
            <label>ID Client</label>
            <span>#CL-<?php echo htmlspecialchars($clientData['id']); ?></span>
        </div>

        <div class="client-info">
            <label>Email</label>
            <span><?php echo htmlspecialchars($clientData['email']); ?></span>
        </div>

        <div class="client-info">
            <label>Téléphone</label>
            <span><?php echo htmlspecialchars($clientData['telephone'] ?? '-'); ?></span>
        </div>

        <div class="client-info">
            <label>Adresse</label>
            <span><?php echo htmlspecialchars($clientData['adresse'] ?? '-'); ?></span>
        </div>

        <div class="client-info">
            <label>Ville</label>
            <span><?php echo htmlspecialchars($clientData['ville'] ?? '-'); ?></span>
        </div>

        <div class="client-info">
            <label>Code Postal</label>
            <span><?php echo htmlspecialchars($clientData['code_postal'] ?? '-'); ?></span>
        </div>

        <div class="client-info">
            <label>Commercial assigné</label>
            <span><?php echo htmlspecialchars($clientData['commercial_nom'] ?? '-'); ?></span>
        </div>

        <div class="client-info">
            <label>Nombre de commandes</label>
            <span><?php echo $controller->getCommandesCount($clientData['id']); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
