<?php
require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../config/Database.php';

/**
 * PageProduitController - Contrôleur pour la gestion des produits/stock
 * Accessible par : Manager, Magasinier
 */
class ProduitController {

    private $produitModel;
    private $db;
    private $message = '';
    private $error = '';
    private $action = 'list';
    private $produitId = null;
    private $produitData = null;
    private $produits = [];
    private $statistiques = [];
    private $search = '';

    public function __construct() {
        $this->produitModel = new Produit();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Vérifier les permissions d'accès
     * Stock/Produits accessibles par : Manager, Magasinier
     */
    public function checkAccess(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'Magasinier']);
    }

    /**
     * Traiter la requête et préparer les données
     */
    public function handleRequest(): void {
        $this->action = $_GET['action'] ?? 'list';
        $this->produitId = $_GET['id'] ?? null;
        $this->search = $_GET['search'] ?? '';

        // Traitement des actions POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequest();
        }

        // Charger les données
        $this->loadData();
    }

    /**
     * Traiter les requêtes POST
     */
    private function handlePostRequest(): void {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'add':
                $this->createProduit($_POST);
                break;

            case 'edit':
                if (isset($_POST['id'])) {
                    $this->updateProduit($_POST['id'], $_POST);
                }
                break;

            case 'delete':
                if (isset($_POST['id'])) {
                    $this->deleteProduit($_POST['id']);
                }
                break;

            case 'ajuster':
                if (isset($_POST['id']) && isset($_POST['new_stock'])) {
                    $this->ajusterStock($_POST['id'], $_POST['new_stock']);
                }
                break;
        }
    }

    /**
     * Créer un nouveau produit
     */
    private function createProduit(array $data): void {
        $this->produitModel->setNom($data['nom'] ?? '');
        $this->produitModel->setDescription($data['description'] ?? '');
        $this->produitModel->setPrixUnitaire($data['prix_unitaire'] ?? 0);
        $this->produitModel->setStockActuel($data['stock_actuel'] ?? 0);
        $this->produitModel->setStockMin($data['stock_min'] ?? 10);
        $this->produitModel->setCategorie($data['categorie'] ?? '');

        if ($this->produitModel->create()) {
            $this->message = 'Produit créé avec succès!';
            $this->action = 'list';
        } else {
            $this->error = 'Erreur lors de la création du produit.';
        }
    }

    /**
     * Mettre à jour un produit
     */
    private function updateProduit(int $id, array $data): void {
        $this->produitModel->setNom($data['nom'] ?? '');
        $this->produitModel->setDescription($data['description'] ?? '');
        $this->produitModel->setPrixUnitaire($data['prix_unitaire'] ?? 0);
        $this->produitModel->setStockActuel($data['stock_actuel'] ?? 0);
        $this->produitModel->setStockMin($data['stock_min'] ?? 10);
        $this->produitModel->setCategorie($data['categorie'] ?? '');

        if ($this->produitModel->update($id)) {
            $this->message = 'Produit modifié avec succès!';
            $this->action = 'list';
        } else {
            $this->error = 'Erreur lors de la modification du produit.';
        }
    }

    /**
     * Supprimer un produit
     */
    private function deleteProduit(int $id): void {
        if ($this->produitModel->delete($id)) {
            $this->message = 'Produit supprimé avec succès!';
        } else {
            $this->error = 'Erreur lors de la suppression du produit.';
        }
        $this->action = 'list';
    }

    /**
     * Ajuster le stock d'un produit
     */
    private function ajusterStock(int $id, int $newStock): void {
        $produit = $this->produitModel->read($id);

        if (!$produit) {
            $this->error = 'Produit non trouvé.';
            return;
        }

        $this->produitModel->setNom($produit['nom']);
        $this->produitModel->setDescription($produit['description']);
        $this->produitModel->setPrixUnitaire($produit['prix_unitaire']);
        $this->produitModel->setStockActuel($newStock);
        $this->produitModel->setStockMin($produit['stock_min']);
        $this->produitModel->setCategorie($produit['categorie']);

        if ($this->produitModel->update($id)) {
            $this->message = 'Stock ajusté avec succès!';
        } else {
            $this->error = 'Erreur lors de l\'ajustement du stock.';
        }
        $this->action = 'list';
    }

    /**
     * Charger les données
     */
    private function loadData(): void {
        // Charger les données pour l'édition
        if ($this->action === 'edit' && $this->produitId) {
            $this->produitData = $this->produitModel->read($this->produitId);
            if (!$this->produitData) {
                $this->error = 'Produit non trouvé.';
                $this->action = 'list';
            }
        }

        // Récupérer tous les produits
        $this->produits = $this->produitModel->getAll();

        // Recherche
        if (!empty($this->search)) {
            $searchLower = strtolower($this->search);
            $this->produits = array_filter($this->produits, function($p) use ($searchLower) {
                return strpos(strtolower($p['nom']), $searchLower) !== false ||
                       strpos(strtolower($p['categorie'] ?? ''), $searchLower) !== false;
            });
        }

        // Calculer les statistiques
        $this->calculateStatistiques();
    }

    /**
     * Calculer les statistiques du stock
     */
    private function calculateStatistiques(): void {
        $allProduits = $this->produitModel->getAll();

        $totalProduits = count($allProduits);
        $valeurStock = 0;
        $stockFaible = 0;

        foreach ($allProduits as $p) {
            $valeurStock += $p['prix_unitaire'] * $p['stock_actuel'];
            if ($p['stock_actuel'] <= $p['stock_min']) {
                $stockFaible++;
            }
        }

        $this->statistiques = [
            'total_produits' => $totalProduits,
            'valeur_stock' => $valeurStock,
            'stock_faible' => $stockFaible
        ];
    }

    /**
     * Obtenir le niveau de stock
     */
    public static function getStockLevel(int $stock, int $stockMin): string {
        if ($stock <= 0) return 'low';
        if ($stock <= $stockMin) return 'medium';
        return 'high';
    }

    /**
     * Obtenir la classe CSS de catégorie
     */
    public static function getCategoryClass(string $categorie): string {
        $cat = strtolower($categorie);
        if (strpos($cat, 'electron') !== false || strpos($cat, 'informat') !== false) return 'electronique';
        if (strpos($cat, 'access') !== false) return 'accessoires';
        return 'materiel';
    }

    // Getters pour la vue
    public function getMessage(): string { return $this->message; }
    public function getError(): string { return $this->error; }
    public function getAction(): string { return $this->action; }
    public function getProduitId(): ?int { return $this->produitId; }
    public function getProduitData(): ?array { return $this->produitData; }
    public function getProduits(): array { return $this->produits; }
    public function getStatistiques(): array { return $this->statistiques; }
    public function getSearch(): string { return $this->search; }
    public function hasAccess(): bool { return $this->checkAccess(); }
}
