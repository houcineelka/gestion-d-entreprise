<?php
/**
 * Vue Commandes - Affichage de la gestion des commandes
 * La logique métier est gérée par PageCommandeController
 */

$pageTitle = 'Gestion des Commandes';
$additionalCss = './styles/commandes.css';

// Charger le contrôleur
require_once __DIR__ . '/../controllers/CommandeController.php';

// Initialiser et traiter la requête
$controller = new CommandeController();
$controller->handleRequest();

// Récupérer les données du contrôleur
$message = $controller->getMessage();
$error = $controller->getError();
$action = $controller->getAction();
$commande = $controller->getCommande();
$details = $controller->getDetails();
$commandes = $controller->getCommandes();
$clients = $controller->getClients();
$produits = $controller->getProduits();
$filterStatut = $controller->getFilterStatut();
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
    <!-- Liste des commandes -->
    <div class="header">
        <h1>Gestion des Commandes</h1>
        <div class="header-buttons">
            <a href="commandes.php?action=add" class="btn-primary">+ Nouvelle Commande</a>
        </div>
    </div>

    <div class="toolbar">
        <div class="status-tabs">
            <a href="commandes.php" class="status-tab <?php echo empty($filterStatut) ? 'active' : ''; ?>">Toutes</a>
            <a href="commandes.php?statut=en_attente" class="status-tab <?php echo $filterStatut === 'en_attente' ? 'active' : ''; ?>">En Attente</a>
            <a href="commandes.php?statut=validee" class="status-tab <?php echo $filterStatut === 'validee' ? 'active' : ''; ?>">Validées</a>
            <a href="commandes.php?statut=livree" class="status-tab <?php echo $filterStatut === 'livree' ? 'active' : ''; ?>">Livrées</a>
            <a href="commandes.php?statut=annulee" class="status-tab <?php echo $filterStatut === 'annulee' ? 'active' : ''; ?>">Annulées</a>
        </div>
        <form method="GET" action="" class="search-box">
            <input type="text" name="search" placeholder="Rechercher une commande..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="action-btn">Rechercher</button>
        </form>
    </div>

    <div class="orders-list">
        <?php if (empty($commandes)): ?>
            <div class="alert alert-info">Aucune commande trouvée.</div>
        <?php else: ?>
            <?php foreach ($commandes as $cmd): ?>
                <?php $cmdDetails = $controller->getCommandeDetails($cmd['id']); ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3><?php echo htmlspecialchars($cmd['client_nom']); ?></h3>
                            <div class="order-id">Commande: CMD-<?php echo date('Y', strtotime($cmd['date_commande'])); ?>-<?php echo str_pad($cmd['id'], 4, '0', STR_PAD_LEFT); ?></div>
                            <div class="order-date"><?php echo date('d F Y - H:i', strtotime($cmd['date_commande'])); ?></div>
                        </div>
                        <span class="status-badge <?php echo CommandeController::getStatusClass($cmd['statut']); ?>">
                            <?php echo CommandeController::getStatusText($cmd['statut']); ?>
                        </span>
                    </div>

                    <div class="order-details">
                        <div class="detail-item">
                            <span class="detail-label">Commercial</span>
                            <span class="detail-value"><?php echo htmlspecialchars($cmd['commercial_nom']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Articles</span>
                            <span class="detail-value"><?php echo count($cmdDetails); ?> produit(s)</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Livraison</span>
                            <span class="detail-value"><?php echo $cmd['date_livraison'] ? date('d/m/Y', strtotime($cmd['date_livraison'])) : 'Non définie'; ?></span>
                        </div>
                        <?php if ($cmd['remise_pourcentage'] > 0): ?>
                        <div class="detail-item">
                            <span class="detail-label">Remise</span>
                            <span class="detail-value"><?php echo number_format($cmd['remise_pourcentage'], 0); ?>%</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($cmdDetails)): ?>
                    <div class="products-section">
                        <div class="products-title">Produits commandés:</div>
                        <div class="products-list">
                            <?php foreach ($cmdDetails as $detail): ?>
                            <div class="product-item">
                                <div class="product-info">
                                    <div class="product-icon">📦</div>
                                    <div class="product-details">
                                        <h4><?php echo htmlspecialchars($detail['produit_nom']); ?></h4>
                                        <div class="product-code">Prix unitaire: <?php echo number_format($detail['prix_unitaire'], 2); ?> €</div>
                                    </div>
                                </div>
                                <div class="product-quantity">
                                    <span class="qty">x<?php echo $detail['quantite']; ?></span>
                                    <span class="price"><?php echo number_format($detail['sous_total'], 2); ?> €</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="order-footer">
                        <div class="total">
                            <span class="total-label">Montant Total</span>
                            <span class="total-value"><?php echo number_format($cmd['montant_final'], 2); ?> €</span>
                        </div>
                        <div class="order-actions">
                            <a href="commandes.php?action=view&id=<?php echo $cmd['id']; ?>" class="action-btn">Voir</a>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="update_statut">
                                <input type="hidden" name="id" value="<?php echo $cmd['id']; ?>">
                                <select name="statut" onchange="this.form.submit()" style="padding: 8px; border-radius: 4px; border: 1px solid #ddd;">
                                    <option value="en_attente" <?php echo $cmd['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                    <option value="validee" <?php echo $cmd['statut'] === 'validee' ? 'selected' : ''; ?>>Validée</option>
                                    <option value="livree" <?php echo $cmd['statut'] === 'livree' ? 'selected' : ''; ?>>Livrée</option>
                                    <option value="annulee" <?php echo $cmd['statut'] === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'add'): ?>
    <!-- Formulaire de nouvelle commande -->
    <div class="header">
        <h1>Nouvelle Commande</h1>
        <a href="commandes.php" class="btn-secondary">Retour à la liste</a>
    </div>

    <div class="form-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="client_id">Client *</label>
                <select id="client_id" name="client_id" required>
                    <option value="">-- Sélectionner un client --</option>
                    <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['id']; ?>">
                        <?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom'] . ' (' . $client['email'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="date_livraison">Date de Livraison</label>
                <input type="date" id="date_livraison" name="date_livraison" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
            </div>

            <div class="form-group">
                <label>Produits *</label>
                <div class="alert alert-info">Sélectionnez les produits et leurs quantités</div>

                <table>
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Prix Unitaire</th>
                            <th>Stock</th>
                            <th>Quantité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produits as $produit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                            <td><?php echo number_format($produit['prix_unitaire'], 2); ?> €</td>
                            <td><?php echo $produit['stock_actuel']; ?></td>
                            <td>
                                <input type="number" name="produits[<?php echo $produit['id']; ?>]"
                                       min="0" max="<?php echo $produit['stock_actuel']; ?>"
                                       value="0" style="width: 80px; padding: 5px;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-primary">Créer la Commande</button>
                <a href="commandes.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

<?php elseif ($action === 'view' && $commande): ?>
    <!-- Détails de la commande -->
    <div class="header">
        <h1>Commande #<?php echo $commande['id']; ?></h1>
        <a href="commandes.php" class="btn-secondary">Retour à la liste</a>
    </div>

    <div class="order-card">
        <div class="order-header">
            <div class="order-info">
                <h3><?php echo htmlspecialchars($commande['client_nom']); ?></h3>
                <div class="order-id">Commande: CMD-<?php echo date('Y', strtotime($commande['date_commande'])); ?>-<?php echo str_pad($commande['id'], 4, '0', STR_PAD_LEFT); ?></div>
                <div class="order-date"><?php echo date('d F Y - H:i', strtotime($commande['date_commande'])); ?></div>
            </div>
            <span class="status-badge <?php echo CommandeController::getStatusClass($commande['statut']); ?>">
                <?php echo CommandeController::getStatusText($commande['statut']); ?>
            </span>
        </div>

        <div class="order-details">
            <div class="detail-item">
                <span class="detail-label">Commercial</span>
                <span class="detail-value"><?php echo htmlspecialchars($commande['commercial_nom']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Email Client</span>
                <span class="detail-value"><?php echo htmlspecialchars($commande['client_email']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Livraison prévue</span>
                <span class="detail-value"><?php echo $commande['date_livraison'] ? date('d/m/Y', strtotime($commande['date_livraison'])) : 'Non définie'; ?></span>
            </div>
            <?php if ($commande['remise_pourcentage'] > 0): ?>
            <div class="detail-item">
                <span class="detail-label">Remise appliquée</span>
                <span class="detail-value"><?php echo number_format($commande['remise_pourcentage'], 0); ?>%</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="products-section">
            <div class="products-title">Produits commandés:</div>
            <div class="products-list">
                <?php foreach ($details as $detail): ?>
                <div class="product-item">
                    <div class="product-info">
                        <div class="product-icon">📦</div>
                        <div class="product-details">
                            <h4><?php echo htmlspecialchars($detail['produit_nom']); ?></h4>
                            <div class="product-code">Prix unitaire: <?php echo number_format($detail['prix_unitaire'], 2); ?> €</div>
                        </div>
                    </div>
                    <div class="product-quantity">
                        <span class="qty">x<?php echo $detail['quantite']; ?></span>
                        <span class="price"><?php echo number_format($detail['sous_total'], 2); ?> €</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="order-footer">
            <div class="total">
                <span class="total-label">Montant Total</span>
                <span class="total-value"><?php echo number_format($commande['montant_final'], 2); ?> €</span>
            </div>
            <div class="order-actions">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_statut">
                    <input type="hidden" name="id" value="<?php echo $commande['id']; ?>">
                    <select name="statut" style="padding: 10px; border-radius: 4px; border: 1px solid #ddd; margin-right: 10px;">
                        <option value="en_attente" <?php echo $commande['statut'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="validee" <?php echo $commande['statut'] === 'validee' ? 'selected' : ''; ?>>Validée</option>
                        <option value="livree" <?php echo $commande['statut'] === 'livree' ? 'selected' : ''; ?>>Livrée</option>
                        <option value="annulee" <?php echo $commande['statut'] === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                    <button type="submit" class="btn-primary">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
