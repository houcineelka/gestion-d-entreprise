<?php
require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../config/Database.php';

/**
 * PageCommandeController - Contrôleur pour la gestion des commandes
 * Accessible par : Manager, Commercial
 */
class CommandeController {

    private $commandeModel;
    private $clientModel;
    private $produitModel;
    private $db;
    private $message = '';
    private $error = '';
    private $action = 'list';
    private $commandeId = null;
    private $commande = null;
    private $details = [];
    private $commandes = [];
    private $clients = [];
    private $produits = [];
    private $filterStatut = '';
    private $search = '';

    public function __construct() {
        $this->commandeModel = new Commande();
        $this->clientModel = new Client();
        $this->produitModel = new Produit();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Vérifier les permissions d'accès
     * Commandes accessibles par : Manager, Commercial
     */
    public function checkAccess(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'Commercial']);
    }

    /**
     * Traiter la requête et préparer les données
     */
    public function handleRequest(): void {
        $this->action = $_GET['action'] ?? 'list';
        $this->commandeId = $_GET['id'] ?? null;
        $this->filterStatut = $_GET['statut'] ?? '';
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
                $this->createCommande($_POST);
                break;

            case 'update_statut':
                if (isset($_POST['id']) && isset($_POST['statut'])) {
                    $this->updateStatut($_POST['id'], $_POST['statut']);
                }
                break;
        }
    }

    /**
     * Créer une nouvelle commande
     */
    private function createCommande(array $data): void {
        // Calculer les montants
        $montantTotal = $this->calculerMontantTotal($data['produits'] ?? []);

        if ($montantTotal <= 0) {
            $this->error = 'Veuillez sélectionner au moins un produit.';
            return;
        }

        $remisePourcentage = 0;
        $montantFinal = $montantTotal * (1 - $remisePourcentage / 100);

        $this->commandeModel->setClientId($data['client_id']);
        $this->commandeModel->setCommercialId($_SESSION['user_id']);
        $this->commandeModel->setMontantTotal($montantTotal);
        $this->commandeModel->setRemisePourcentage($remisePourcentage);
        $this->commandeModel->setMontantFinal($montantFinal);
        $this->commandeModel->setStatut('en_attente');
        $this->commandeModel->setDateLivraison($data['date_livraison'] ?? null);

        $newCommandeId = $this->commandeModel->create();

        if ($newCommandeId) {
            // Ajouter les détails et mettre à jour le stock
            $this->addDetailsAndUpdateStock($newCommandeId, $data['produits'] ?? []);
            $this->message = 'Commande créée avec succès!';
            $this->action = 'list';
        } else {
            $this->error = 'Erreur lors de la création de la commande.';
        }
    }

    /**
     * Calculer le montant total d'une commande
     */
    private function calculerMontantTotal(array $produits): float {
        $montantTotal = 0;

        foreach ($produits as $produit_id => $quantite) {
            if ($quantite > 0) {
                $produit = $this->produitModel->read($produit_id);
                if ($produit) {
                    $montantTotal += $produit['prix_unitaire'] * $quantite;
                }
            }
        }

        return $montantTotal;
    }

    /**
     * Ajouter les détails de commande et mettre à jour le stock
     */
    private function addDetailsAndUpdateStock(int $commandeId, array $produits): void {
        foreach ($produits as $produit_id => $quantite) {
            if ($quantite > 0) {
                $produit = $this->produitModel->read($produit_id);
                if ($produit) {
                    $this->commandeModel->addDetail(
                        $commandeId,
                        $produit_id,
                        $quantite,
                        $produit['prix_unitaire']
                    );
                    // Mettre à jour le stock
                    $this->produitModel->updateStock($produit_id, $quantite);
                }
            }
        }
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    private function updateStatut(int $id, string $statut): void {
        if ($this->commandeModel->updateStatut($id, $statut)) {
            $this->message = 'Statut de la commande mis à jour!';
        } else {
            $this->error = 'Erreur lors de la mise à jour du statut.';
        }
        $this->action = 'list';
    }

    /**
     * Charger les données
     */
    private function loadData(): void {
        // Récupérer toutes les commandes
        $this->commandes = $this->commandeModel->getAll();

        // Filtrer par statut
        if (!empty($this->filterStatut)) {
            $this->commandes = array_filter($this->commandes, function($cmd) {
                return $cmd['statut'] === $this->filterStatut;
            });
        }

        // Recherche
        if (!empty($this->search)) {
            $searchLower = strtolower($this->search);
            $this->commandes = array_filter($this->commandes, function($cmd) use ($searchLower) {
                return strpos(strtolower($cmd['client_nom'] ?? ''), $searchLower) !== false ||
                       strpos(strtolower($cmd['commercial_nom'] ?? ''), $searchLower) !== false ||
                       strpos(strtolower((string)$cmd['id']), $searchLower) !== false;
            });
        }

        // Charger les données pour la vue
        if ($this->action === 'view' && $this->commandeId) {
            $this->commande = $this->commandeModel->read($this->commandeId);
            $this->details = $this->commandeModel->getDetails($this->commandeId);
            if (!$this->commande) {
                $this->error = 'Commande non trouvée.';
                $this->action = 'list';
            }
        }

        // Charger les clients et produits pour le formulaire
        $this->clients = $this->clientModel->getAll();
        $this->produits = $this->produitModel->getAll();
    }

    /**
     * Obtenir les détails d'une commande
     */
    public function getCommandeDetails(int $commandeId): array {
        return $this->commandeModel->getDetails($commandeId);
    }

    /**
     * Obtenir la classe CSS du statut
     */
    public static function getStatusClass(string $statut): string {
        switch ($statut) {
            case 'en_attente': return 'pending';
            case 'validee': return 'processing';
            case 'livree': return 'delivered';
            case 'annulee': return 'shipped';
            default: return 'pending';
        }
    }

    /**
     * Obtenir le texte du statut
     */
    public static function getStatusText(string $statut): string {
        switch ($statut) {
            case 'en_attente': return 'En Attente';
            case 'validee': return 'Validée';
            case 'livree': return 'Livrée';
            case 'annulee': return 'Annulée';
            default: return ucfirst($statut);
        }
    }

    // Getters pour la vue
    public function getMessage(): string { return $this->message; }
    public function getError(): string { return $this->error; }
    public function getAction(): string { return $this->action; }
    public function getCommandeId(): ?int { return $this->commandeId; }
    public function getCommande(): ?array { return $this->commande; }
    public function getDetails(): array { return $this->details; }
    public function getCommandes(): array { return $this->commandes; }
    public function getClients(): array { return $this->clients; }
    public function getProduits(): array { return $this->produits; }
    public function getFilterStatut(): string { return $this->filterStatut; }
    public function getSearch(): string { return $this->search; }
    public function hasAccess(): bool { return $this->checkAccess(); }
}
