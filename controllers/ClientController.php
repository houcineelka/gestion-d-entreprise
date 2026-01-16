<?php
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/Database.php';

/**
 * PageClientController - Contrôleur pour la gestion des clients
 * Accessible par : Manager, Commercial
 */
class ClientController {

    private $clientModel;
    private $userModel;
    private $db;
    private $message = '';
    private $error = '';
    private $action = 'list';
    private $clientId = null;
    private $clientData = null;
    private $clients = [];
    private $commerciaux = [];
    private $search = '';

    public function __construct() {
        $this->clientModel = new Client();
        $this->userModel = new User();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Vérifier les permissions d'accès
     * Clients accessibles par : Manager, Commercial
     */
    public function checkAccess(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'Commercial']);
    }

    /**
     * Traiter la requête et préparer les données
     */
    public function handleRequest(): void {
        $this->action = $_GET['action'] ?? 'list';
        $this->clientId = $_GET['id'] ?? null;
        $this->search = $_GET['search'] ?? '';

        // Récupérer les commerciaux pour le formulaire
        $this->loadCommerciaux();

        // Traitement des actions POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequest();
        }

        // Charger les données selon l'action
        $this->loadData();
    }

    /**
     * Traiter les requêtes POST
     */
    private function handlePostRequest(): void {
        $postAction = $_POST['action'] ?? '';

        switch ($postAction) {
            case 'add':
                $this->createClient($_POST);
                break;

            case 'edit':
                if (isset($_POST['id'])) {
                    $this->updateClient($_POST['id'], $_POST);
                }
                break;

            case 'delete':
                if (isset($_POST['id'])) {
                    $this->deleteClient($_POST['id']);
                }
                break;
        }
    }

    /**
     * Créer un nouveau client
     */
    private function createClient(array $data): void {
        $this->clientModel->setNom($data['nom'] ?? '');
        $this->clientModel->setPrenom($data['prenom'] ?? '');
        $this->clientModel->setEmail($data['email'] ?? '');
        $this->clientModel->setTelephone($data['telephone'] ?? '');
        $this->clientModel->setAdresse($data['adresse'] ?? '');
        $this->clientModel->setVille($data['ville'] ?? '');
        $this->clientModel->setCodePostal($data['code_postal'] ?? '');
        $this->clientModel->setCommercialId($data['commercial_id'] ?? $_SESSION['user_id']);

        if ($this->clientModel->create()) {
            $this->message = 'Client créé avec succès!';
            $this->action = 'list';
        } else {
            $this->error = 'Erreur lors de la création du client.';
        }
    }

    /**
     * Mettre à jour un client
     */
    private function updateClient(int $id, array $data): void {
        $this->clientModel->setNom($data['nom'] ?? '');
        $this->clientModel->setPrenom($data['prenom'] ?? '');
        $this->clientModel->setEmail($data['email'] ?? '');
        $this->clientModel->setTelephone($data['telephone'] ?? '');
        $this->clientModel->setAdresse($data['adresse'] ?? '');
        $this->clientModel->setVille($data['ville'] ?? '');
        $this->clientModel->setCodePostal($data['code_postal'] ?? '');
        $this->clientModel->setCommercialId($data['commercial_id'] ?? null);

        if ($this->clientModel->update($id)) {
            $this->message = 'Client modifié avec succès!';
            $this->action = 'list';
        } else {
            $this->error = 'Erreur lors de la modification du client.';
        }
    }

    /**
     * Supprimer un client (suppression logique)
     */
    private function deleteClient(int $id): void {
        $commandesCount = $this->getCommandesCount($id);

        if ($commandesCount > 0) {
            $this->error = "Impossible de supprimer ce client : il possède $commandesCount commande(s).";
        } elseif ($this->clientModel->delete($id)) {
            $this->message = 'Client supprimé avec succès!';
        } else {
            $this->error = 'Erreur lors de la suppression du client.';
        }
        $this->action = 'list';
    }

    /**
     * Compter les commandes d'un client
     */
    public function getCommandesCount(int $clientId): int {
        try {
            $sql = "SELECT COUNT(*) as count FROM commandes WHERE client_id = :client_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':client_id' => $clientId]);
            $result = $stmt->fetch();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Charger les commerciaux pour le formulaire
     */
    private function loadCommerciaux(): void {
        try {
            $allUsers = $this->userModel->getAll();
            foreach ($allUsers as $user) {
                if ($user['categorie_nom'] === 'Commercial' || $user['categorie_nom'] === 'Manager') {
                    $this->commerciaux[] = $user;
                }
            }
        } catch (Exception $e) {
            $this->commerciaux = [];
        }
    }

    /**
     * Charger les données selon l'action
     */
    private function loadData(): void {
        // Charger les données pour l'édition/visualisation
        if (in_array($this->action, ['edit', 'view']) && $this->clientId) {
            $this->clientData = $this->clientModel->read($this->clientId);
            if (!$this->clientData) {
                $this->error = 'Client non trouvé.';
                $this->action = 'list';
            }
        }

        // Récupérer tous les clients
        $this->clients = $this->clientModel->getAll();

        // Recherche
        if (!empty($this->search)) {
            $this->clients = $this->searchClients($this->clients, $this->search);
        }
    }

    /**
     * Rechercher des clients
     */
    private function searchClients(array $clients, string $search): array {
        $searchLower = strtolower($search);
        return array_filter($clients, function($client) use ($searchLower) {
            return strpos(strtolower($client['nom']), $searchLower) !== false ||
                   strpos(strtolower($client['prenom']), $searchLower) !== false ||
                   strpos(strtolower($client['email']), $searchLower) !== false ||
                   strpos(strtolower($client['ville'] ?? ''), $searchLower) !== false;
        });
    }

    // Getters pour la vue
    public function getMessage(): string { return $this->message; }
    public function getError(): string { return $this->error; }
    public function getAction(): string { return $this->action; }
    public function getClientId(): ?int { return $this->clientId; }
    public function getClientData(): ?array { return $this->clientData; }
    public function getClients(): array { return $this->clients; }
    public function getCommerciaux(): array { return $this->commerciaux; }
    public function getSearch(): string { return $this->search; }
    public function hasAccess(): bool { return $this->checkAccess(); }
}
