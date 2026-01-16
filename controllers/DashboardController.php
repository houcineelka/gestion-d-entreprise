<?php
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../models/User.php';

/**
 * PageDashboardController - Contrôleur pour le tableau de bord
 * Accessible par tous les utilisateurs connectés
 */
class DashboardController {

    private $clientModel;
    private $commandeModel;
    private $produitModel;
    private $userModel;
    private $statistiques = [];

    public function __construct() {
        $this->clientModel = new Client();
        $this->commandeModel = new Commande();
        $this->produitModel = new Produit();
        $this->userModel = new User();
    }

    /**
     * Traiter la requête et préparer les données
     */
    public function handleRequest(): void {
        $this->calculateStatistiques();
    }

    /**
     * Calculer les statistiques du dashboard
     */
    private function calculateStatistiques(): void {
        $this->statistiques = [
            'total_clients' => count($this->clientModel->getAll()),
            'total_commandes' => count($this->commandeModel->getAll()),
            'total_produits' => count($this->produitModel->getAll()),
            'total_utilisateurs' => count($this->userModel->getAll())
        ];
    }

    /**
     * Vérifier si l'utilisateur peut voir les primes (Manager ou RH)
     */
    public function canViewPrimes(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'RH']);
    }

    /**
     * Vérifier si l'utilisateur peut voir les clients (Manager ou Commercial)
     */
    public function canViewClients(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'Commercial']);
    }

    /**
     * Vérifier si l'utilisateur peut voir les commandes (Manager ou Commercial)
     */
    public function canViewCommandes(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'Commercial']);
    }

    /**
     * Vérifier si l'utilisateur peut voir le stock (Manager ou Magasinier)
     */
    public function canViewStock(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'Magasinier']);
    }

    // Getters pour la vue
    public function getStatistiques(): array { return $this->statistiques; }
    public function getTotalClients(): int { return $this->statistiques['total_clients'] ?? 0; }
    public function getTotalCommandes(): int { return $this->statistiques['total_commandes'] ?? 0; }
    public function getTotalProduits(): int { return $this->statistiques['total_produits'] ?? 0; }
    public function getTotalUtilisateurs(): int { return $this->statistiques['total_utilisateurs'] ?? 0; }
}
