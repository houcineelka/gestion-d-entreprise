<?php
/**
 * Vue Historique Utilisateurs - Affichage de l'historique des actions
 * La logique métier est gérée par HistoriqueController
 */

$pageTitle = 'Historique des Utilisateurs';
$additionalCss = './styles/historique.css';

// Charger le contrôleur
require_once __DIR__ . '/../controllers/HistoriqueController.php';

// Initialiser et traiter la requête
$controller = new HistoriqueController();
$controller->handleRequest();

// Récupérer les données du contrôleur
$historique = $controller->getHistorique();
$statistiques = $controller->getStatistiques();
$filterUser = $controller->getFilterUser();
$filterAction = $controller->getFilterAction();
$filterTable = $controller->getFilterTable();
$dateDebut = $controller->getDateDebut();
$dateFin = $controller->getDateFin();
$utilisateurs = $controller->getUtilisateurs();
$currentPage = $controller->getCurrentPage();
$totalPages = $controller->getTotalPages();

require_once __DIR__ . '/../includes/header.php';

// Vérifier que l'utilisateur a le droit d'accès
if (!$controller->hasAccess()) {
    echo '<div class="alert alert-error">Accès non autorisé. Cette page est réservée aux RH et Managers.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}
?>

<!-- En-tête -->
<div class="header">
    <h1>Historique des Actions Utilisateurs</h1>
  
</div>

<!-- Statistiques -->
<div class="stats">
    <div class="stat-box">
        <div class="stat-label">Total Actions</div>
        <div class="stat-value"><?php echo $statistiques['total_actions']; ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Aujourd'hui</div>
        <div class="stat-value"><?php echo $statistiques['today_actions']; ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Cette Semaine</div>
        <div class="stat-value"><?php echo $statistiques['week_actions']; ?></div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Ce Mois</div>
        <div class="stat-value"><?php echo $statistiques['month_actions']; ?></div>
    </div>
</div>

<!-- Filtres -->
<div class="filters-container">
    <form method="GET" action="" class="filters-form">
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter_user">Utilisateur</label>
                <select id="filter_user" name="user_id">
                    <option value="">Tous les utilisateurs</option>
                    <?php foreach ($utilisateurs as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo ($filterUser == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter_action">Type d'Action</label>
                <select id="filter_action" name="action">
                    <option value="">Toutes les actions</option>
                    <option value="CREATE" <?php echo ($filterAction === 'CREATE') ? 'selected' : ''; ?>>Création</option>
                    <option value="UPDATE" <?php echo ($filterAction === 'UPDATE') ? 'selected' : ''; ?>>Modification</option>
                    <option value="DELETE" <?php echo ($filterAction === 'DELETE') ? 'selected' : ''; ?>>Suppression</option>
                    <option value="LOGIN" <?php echo ($filterAction === 'LOGIN') ? 'selected' : ''; ?>>Connexion</option>
                    <option value="LOGOUT" <?php echo ($filterAction === 'LOGOUT') ? 'selected' : ''; ?>>Déconnexion</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter_table">Table</label>
                <select id="filter_table" name="table">
                    <option value="">Toutes les tables</option>
                    <option value="utilisateurs" <?php echo ($filterTable === 'utilisateurs') ? 'selected' : ''; ?>>Utilisateurs</option>
                    <option value="produits" <?php echo ($filterTable === 'produits') ? 'selected' : ''; ?>>Produits</option>
                    <option value="commandes" <?php echo ($filterTable === 'commandes') ? 'selected' : ''; ?>>Commandes</option>
                    <option value="clients" <?php echo ($filterTable === 'clients') ? 'selected' : ''; ?>>Clients</option>
                </select>
            </div>
        </div>

        <div class="filter-row">
            <div class="filter-group">
                <label for="date_debut">Date Début</label>
                <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($dateDebut); ?>">
            </div>

            <div class="filter-group">
                <label for="date_fin">Date Fin</label>
                <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($dateFin); ?>">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrer</button>
                <a href="historique.php" class="btn-secondary">Réinitialiser</a>
            </div>
        </div>
    </form>
</div>

<!-- Timeline d'historique -->
<div class="timeline-container">
    <?php if (empty($historique)): ?>
        <div class="alert alert-info">Aucune action trouvée pour les critères sélectionnés.</div>
    <?php else: ?>
        <div class="timeline">
            <?php 
            $currentDate = null;
            foreach ($historique as $entry): 
                $entryDate = date('Y-m-d', strtotime($entry['date_action']));
                
                // Afficher le séparateur de date si nécessaire
                if ($currentDate !== $entryDate):
                    $currentDate = $entryDate;
            ?>
                <div class="timeline-date-separator">
                    <span><?php echo date('d/m/Y', strtotime($entryDate)); ?></span>
                </div>
            <?php endif; ?>

            <div class="timeline-item">
                <div class="timeline-marker <?php echo strtolower($entry['type_action']); ?>">
                    <?php echo HistoriqueController::getActionIcon($entry['type_action']); ?>
                </div>
                
                <div class="timeline-content">
                    <div class="timeline-header">
                        <div class="timeline-user">
                            <div class="user-avatar-small">
                                <?php echo HistoriqueController::getInitials($entry['utilisateur_prenom'], $entry['utilisateur_nom']); ?>
                            </div>
                            <div class="user-details">
                                <strong><?php echo htmlspecialchars($entry['utilisateur_prenom'] . ' ' . $entry['utilisateur_nom']); ?></strong>
                                <span class="action-badge <?php echo strtolower($entry['type_action']); ?>">
                                    <?php echo HistoriqueController::getActionLabel($entry['type_action']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="timeline-time">
                            <span class="time-icon">🕐</span>
                            <?php echo date('H:i:s', strtotime($entry['date_action'])); ?>
                        </div>
                    </div>

                    <div class="timeline-body">
                        <div class="action-description">
                            <?php echo HistoriqueController::formatActionDescription($entry); ?>
                        </div>

                        <?php if (!empty($entry['details'])): ?>
                        <div class="action-details">
                            <strong>Détails :</strong>
                            <pre><?php echo htmlspecialchars($entry['details']); ?></pre>
                        </div>
                        <?php endif; ?>

                        <div class="action-meta">
                            <?php if ($entry['table_cible']): ?>
                            <span class="meta-item">
                                <strong>Table :</strong> <?php echo htmlspecialchars($entry['table_cible']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($entry['id_cible']): ?>
                            <span class="meta-item">
                                <strong>ID :</strong> <?php echo htmlspecialchars($entry['id_cible']); ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($entry['ip_address']): ?>
                            <span class="meta-item">
                                <strong>IP :</strong> <?php echo htmlspecialchars($entry['ip_address']); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo ($currentPage - 1); ?><?php echo $filterUser ? '&user_id=' . $filterUser : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterTable ? '&table=' . $filterTable : ''; ?><?php echo $dateDebut ? '&date_debut=' . $dateDebut : ''; ?><?php echo $dateFin ? '&date_fin=' . $dateFin : ''; ?>" class="pagination-btn">
                    ← Précédent
                </a>
            <?php endif; ?>

            <span class="pagination-info">
                Page <?php echo $currentPage; ?> sur <?php echo $totalPages; ?>
            </span>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo ($currentPage + 1); ?><?php echo $filterUser ? '&user_id=' . $filterUser : ''; ?><?php echo $filterAction ? '&action=' . $filterAction : ''; ?><?php echo $filterTable ? '&table=' . $filterTable : ''; ?><?php echo $dateDebut ? '&date_debut=' . $dateDebut : ''; ?><?php echo $dateFin ? '&date_fin=' . $dateFin : ''; ?>" class="pagination-btn">
                    Suivant →
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
