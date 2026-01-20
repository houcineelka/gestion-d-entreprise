<?php

$pageTitle = 'Gestion du Stock';
$additionalCss = './styles/produits.css';

// Charger le contrôleur
require_once __DIR__ . '/../controllers/ProduitController.php';

// Initialiser et traiter la requête
$controller = new ProduitController();
$controller->handleRequest();

// Récupérer les données du contrôleur
$message = $controller->getMessage();
$error = $controller->getError();
$action = $controller->getAction();
$produitData = $controller->getProduitData();
$produits = $controller->getProduits();
$statistiques = $controller->getStatistiques();
$search = $controller->getSearch();

require_once __DIR__ . '/../includes/header.php';

// Vérifier que l'utilisateur a le droit d'accès
if (!$controller->hasAccess()) {
    echo '<div class="alert alert-error">Accès non autorisé. Cette page est réservée aux Managers et Magasiniers.</div>';
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
    <!-- Liste des produits -->
    <div class="header">
        <h1>Gestion du Stock</h1>
        <div class="header-buttons">
            <a href="produits.php?action=add" class="btn-primary">+ Ajouter Produit</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Produits Disponibles</div>
            <div class="stat-value"><?php echo number_format($statistiques['total_produits']); ?></div>
            <div class="stat-change">Total en stock</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Valeur Totale Stock</div>
            <div class="stat-value"><?php echo number_format($statistiques['valeur_stock'], 0, ',', ' '); ?> €</div>
            <div class="stat-change">Valeur estimée</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Stock Faible</div>
            <div class="stat-value"><?php echo $statistiques['stock_faible']; ?></div>
            <div class="stat-change warning">Produits en alerte</div>
        </div>
    </div>

    <div class="toolbar">
        <form method="GET" action="" class="search-box">
            <input type="text" name="search" placeholder="Rechercher un produit..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="filter-btn">Rechercher</button>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Catégorie</th>
                    <th>Stock</th>
                    <th>Prix Unitaire</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($produits)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #666;">Aucun produit trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($produits as $p): ?>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <div class="product-icon">📦</div>
                                    <div>
                                        <div class="product-name"><?php echo htmlspecialchars($p['nom']); ?></div>
                                        <div class="product-sku">ID: #<?php echo $p['id']; ?> | Min: <?php echo $p['stock_min']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="category <?php echo ProduitController::getCategoryClass($p['categorie']); ?>">
                                    <?php echo htmlspecialchars($p['categorie']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="stock-level">
                                    <span class="stock-indicator <?php echo ProduitController::getStockLevel($p['stock_actuel'], $p['stock_min']); ?>"></span>
                                    <?php echo $p['stock_actuel']; ?>
                                </div>
                            </td>
                            <td>
                                <div class="price"><?php echo number_format($p['prix_unitaire'], 2); ?> €</div>
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="ajuster">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <input type="number" name="new_stock" value="<?php echo $p['stock_actuel']; ?>" style="width: 60px; padding: 5px; margin-right: 5px;">
                                        <button type="submit" class="btn-icon">Ajuster</button>
                                    </form>
                                    <a href="produits.php?action=edit&id=<?php echo $p['id']; ?>" class="btn-icon">Modifier</a>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn-icon">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($action === 'add'): ?>
    <!-- Formulaire d'ajout -->
    <div class="header">
        <h1>Nouveau Produit</h1>
        <a href="produits.php" class="btn-secondary">Retour à la liste</a>
    </div>

    <div class="form-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label for="nom">Nom du produit *</label>
                <input type="text" id="nom" name="nom" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="categorie">Catégorie *</label>
                <input type="text" id="categorie" name="categorie" required placeholder="Ex: Informatique, Accessoires, etc.">
            </div>

            <div class="form-group">
                <label for="prix_unitaire">Prix Unitaire (€) *</label>
                <input type="number" id="prix_unitaire" name="prix_unitaire" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="stock_actuel">Stock Actuel *</label>
                <input type="number" id="stock_actuel" name="stock_actuel" min="0" required>
            </div>

            <div class="form-group">
                <label for="stock_min">Stock Minimum *</label>
                <input type="number" id="stock_min" name="stock_min" min="0" required value="10">
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-primary">Créer le produit</button>
                <a href="produits.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

<?php elseif ($action === 'edit' && $produitData): ?>
    <!-- Formulaire de modification -->
    <div class="header">
        <h1>Modifier le Produit</h1>
        <a href="produits.php" class="btn-secondary">Retour à la liste</a>
    </div>

    <div class="form-container">
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $produitData['id']; ?>">

            <div class="form-group">
                <label for="nom">Nom du produit *</label>
                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($produitData['nom']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($produitData['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="categorie">Catégorie *</label>
                <input type="text" id="categorie" name="categorie" value="<?php echo htmlspecialchars($produitData['categorie']); ?>" required>
            </div>

            <div class="form-group">
                <label for="prix_unitaire">Prix Unitaire (€) *</label>
                <input type="number" id="prix_unitaire" name="prix_unitaire" step="0.01" min="0" value="<?php echo $produitData['prix_unitaire']; ?>" required>
            </div>

            <div class="form-group">
                <label for="stock_actuel">Stock Actuel *</label>
                <input type="number" id="stock_actuel" name="stock_actuel" min="0" value="<?php echo $produitData['stock_actuel']; ?>" required>
            </div>

            <div class="form-group">
                <label for="stock_min">Stock Minimum *</label>
                <input type="number" id="stock_min" name="stock_min" min="0" value="<?php echo $produitData['stock_min']; ?>" required>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
                <a href="produits.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
