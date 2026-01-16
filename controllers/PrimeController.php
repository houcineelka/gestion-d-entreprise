<?php
require_once __DIR__ . '/../services/BusinessService.php';

/**
 * PagePrimeController - Contrôleur pour la gestion des primes
 * Accessible par : Manager, RH
 */
class PrimeController {

    private $businessService;
    private $db;
    private $message = '';
    private $error = '';
    private $annee;
    private $listePrimes = [];
    private $listeClients = [];

    public function __construct() {
        $this->businessService = new BusinessService();
        $this->annee = $_GET['annee'] ?? date('Y');
    }

    /**
     * Vérifier les permissions d'accès
     * Primes accessibles par : Manager, RH
     */
    public function checkAccess(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'RH']);
    }

    /**
     * Traiter la requête et préparer les données
     */
    public function handleRequest(): void {
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

        if ($postAction === 'calculer') {
            $anneeCalcul = $_POST['annee'] ?? date('Y');
            $primes = $this->businessService->calculerToutesPrimes($anneeCalcul);
            $this->message = 'Primes calculées et enregistrées pour l\'année ' . $anneeCalcul . ' : ' . count($primes) . ' commercial(aux) traité(s).';
        }
    }

    /**
     * Charger les données
     */
    private function loadData(): void {
        $this->listePrimes = $this->businessService->afficherListePrimes($this->annee);
        $this->listeClients = $this->businessService->afficherListeClientsAvecRemises($this->annee);
    }

    /**
     * Filtrer les clients avec achats
     */
    public function getClientsAvecAchats(): array {
        return array_filter($this->listeClients, function($c) {
            return $c['nb_commandes'] > 0;
        });
    }

    /**
     * Calculer les totaux des primes
     */
    public function getTotauxPrimes(): array {
        $totalCA = 0;
        $totalPrimes = 0;

        foreach ($this->listePrimes as $prime) {
            $totalCA += $prime['chiffre_affaire'];
            $totalPrimes += $prime['montant_prime'];
        }

        return [
            'total_ca' => $totalCA,
            'total_primes' => $totalPrimes
        ];
    }

    /**
     * Calculer les totaux des remises
     */
    public function getTotauxRemises(): array {
        $clientsAvecAchats = $this->getClientsAvecAchats();
        $totalAchats = 0;
        $totalRemises = 0;

        foreach ($clientsAvecAchats as $client) {
            $totalAchats += $client['total_achats'];
            $totalRemises += $client['remise'];
        }

        return [
            'total_achats' => $totalAchats,
            'total_remises' => $totalRemises
        ];
    }

    // Getters pour la vue
    public function getMessage(): string { return $this->message; }
    public function getError(): string { return $this->error; }
    public function getAnnee(): string { return $this->annee; }
    public function getListePrimes(): array { return $this->listePrimes; }
    public function getListeClients(): array { return $this->listeClients; }
    public function hasAccess(): bool { return $this->checkAccess(); }
}
