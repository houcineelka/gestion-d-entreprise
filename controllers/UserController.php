<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/Database.php';

/**
 * PageUserController - Contrôleur pour la gestion des utilisateurs
 * Accessible par : Manager, RH
 */
class UserController {

    private $userModel;
    private $db;
    private $message = '';
    private $error = '';
    private $action = 'list';
    private $userId = null;
    private $userData = null;
    private $utilisateurs = [];
    private $categories = [];
    private $statistiques = [];
    private $search = '';
    private $filterRole = '';

    public function __construct() {
        $this->userModel = new User();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Vérifier les permissions d'accès
     * Utilisateurs accessibles par : Manager, RH
     */
    public function checkAccess(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'RH']);
    }

    /**
     * Traiter la requête et préparer les données
     */
    public function handleRequest(): void {
        $this->action = $_GET['action'] ?? 'list';
        $this->userId = $_GET['id'] ?? null;
        $this->search = $_GET['search'] ?? '';
        $this->filterRole = $_GET['role'] ?? '';

        // Récupérer les catégories
        $this->loadCategories();

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
                $this->createUser($_POST);
                break;

            case 'edit':
                if (isset($_POST['id'])) {
                    $this->updateUser($_POST['id'], $_POST);
                }
                break;

            case 'delete':
                if (isset($_POST['id'])) {
                    $this->deleteUser($_POST['id']);
                }
                break;
        }
    }

    /**
     * Créer un nouvel utilisateur
     */
    private function createUser(array $data): void {
        $this->userModel->setNom($data['nom'] ?? '');
        $this->userModel->setPrenom($data['prenom'] ?? '');
        $this->userModel->setEmail($data['email'] ?? '');
        $this->userModel->setMotDePasse($data['mot_de_passe'] ?? '');
        $this->userModel->setCategorieId($data['categorie_id'] ?? null);
        $this->userModel->setActif(1);

        if ($this->userModel->create()) {
            $this->message = 'Utilisateur créé avec succès!';
            $this->action = 'list';
        } else {
            $this->error = 'Erreur lors de la création de l\'utilisateur.';
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    private function updateUser(int $id, array $data): void {
        $this->userModel->setNom($data['nom'] ?? '');
        $this->userModel->setPrenom($data['prenom'] ?? '');
        $this->userModel->setEmail($data['email'] ?? '');
        $this->userModel->setCategorieId($data['categorie_id'] ?? null);
        $this->userModel->setActif($data['actif'] ?? 1);

        if ($this->userModel->update($id)) {
            $this->message = 'Utilisateur modifié avec succès!';
            $this->action = 'list';
        } else {
            $this->error = 'Erreur lors de la modification de l\'utilisateur.';
        }
    }

    /**
     * Désactiver un utilisateur (suppression logique)
     */
    private function deleteUser(int $id): void {
        if ($id == $_SESSION['user_id']) {
            $this->error = 'Vous ne pouvez pas vous désactiver vous-même.';
        } elseif ($this->userModel->delete($id)) {
            $this->message = 'Utilisateur désactivé avec succès!';
        } else {
            $this->error = 'Erreur lors de la désactivation de l\'utilisateur.';
        }
        $this->action = 'list';
    }

    /**
     * Charger les catégories d'utilisateurs
     */
    private function loadCategories(): void {
        try {
            $stmt = $this->db->query("SELECT * FROM categories_utilisateur ORDER BY nom");
            $this->categories = $stmt->fetchAll();
        } catch (Exception $e) {
            $this->categories = [];
        }
    }

    /**
     * Charger les données
     */
    private function loadData(): void {
        // Charger les données pour l'édition
        if ($this->action === 'edit' && $this->userId) {
            $this->userData = $this->userModel->read($this->userId);
            if (!$this->userData) {
                $this->error = 'Utilisateur non trouvé.';
                $this->action = 'list';
            }
        }

        // Récupérer tous les utilisateurs
        $this->utilisateurs = $this->userModel->getAll();

        // Filtrer les utilisateurs
        $this->utilisateurs = $this->filterUsers($this->utilisateurs);

        // Calculer les statistiques
        $this->calculateStatistiques();
    }

    /**
     * Filtrer les utilisateurs par recherche et rôle
     */
    private function filterUsers(array $utilisateurs): array {
        if (empty($this->search) && empty($this->filterRole)) {
            return $utilisateurs;
        }

        return array_filter($utilisateurs, function($user) {
            $matchSearch = empty($this->search) ||
                strpos(strtolower($user['nom']), strtolower($this->search)) !== false ||
                strpos(strtolower($user['prenom']), strtolower($this->search)) !== false ||
                strpos(strtolower($user['email']), strtolower($this->search)) !== false;

            $matchRole = empty($this->filterRole) || $user['categorie_nom'] === $this->filterRole;

            return $matchSearch && $matchRole;
        });
    }

    /**
     * Calculer les statistiques des utilisateurs
     */
    private function calculateStatistiques(): void {
        $allUsers = $this->userModel->getAll();

        $totalUsers = count($allUsers);
        $activeUsers = count(array_filter($allUsers, function($u) { return $u['actif']; }));
        $managerCount = count(array_filter($allUsers, function($u) { return $u['categorie_nom'] === 'Manager'; }));
        $inactiveUsers = $totalUsers - $activeUsers;

        $this->statistiques = [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'manager_count' => $managerCount,
            'inactive_users' => $inactiveUsers
        ];
    }

    /**
     * Obtenir les initiales d'un utilisateur
     */
    public static function getInitials(string $prenom, string $nom): string {
        return strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1));
    }

    /**
     * Obtenir la classe CSS du badge de rôle
     */
    public static function getRoleBadgeClass(string $categorie): string {
        switch ($categorie) {
            case 'Manager': return 'admin';
            case 'Commercial': return 'commercial';
            case 'RH': return 'rh';
            case 'Magasinier': return 'achat';
            default: return 'commercial';
        }
    }

    /**
     * Obtenir les permissions d'un rôle selon la table fournie:
     * - Manager: Accès complet à toutes les fonctionnalités
     * - Commercial: Gestion des clients et commandes
     * - RH: Gestion des utilisateurs et primes
     * - Magasinier: Gestion des stocks et produits
     */
    public static function getPermissions(string $categorie): array {
        switch ($categorie) {
            case 'Manager':
                return [
                    ['name' => 'Toutes fonctionnalités', 'granted' => true],
                    ['name' => 'Clients', 'granted' => true],
                    ['name' => 'Commandes', 'granted' => true],
                    ['name' => 'Stock/Produits', 'granted' => true],
                    ['name' => 'Utilisateurs', 'granted' => true],
                    ['name' => 'Primes', 'granted' => true]
                ];
            case 'Commercial':
                return [
                    ['name' => 'Clients', 'granted' => true],
                    ['name' => 'Commandes', 'granted' => true],
                    ['name' => 'Stock/Produits', 'granted' => false],
                    ['name' => 'Utilisateurs', 'granted' => false],
                    ['name' => 'Primes', 'granted' => false]
                ];
            case 'RH':
                return [
                    ['name' => 'Utilisateurs', 'granted' => true],
                    ['name' => 'Primes', 'granted' => true],
                    ['name' => 'Clients', 'granted' => false],
                    ['name' => 'Commandes', 'granted' => false],
                    ['name' => 'Stock/Produits', 'granted' => false]
                ];
            case 'Magasinier':
                return [
                    ['name' => 'Stock/Produits', 'granted' => true],
                    ['name' => 'Clients', 'granted' => false],
                    ['name' => 'Commandes', 'granted' => false],
                    ['name' => 'Utilisateurs', 'granted' => false],
                    ['name' => 'Primes', 'granted' => false]
                ];
            default:
                return [];
        }
    }

    // Getters pour la vue
    public function getMessage(): string { return $this->message; }
    public function getError(): string { return $this->error; }
    public function getAction(): string { return $this->action; }
    public function getUserId(): ?int { return $this->userId; }
    public function getUserData(): ?array { return $this->userData; }
    public function getUtilisateurs(): array { return $this->utilisateurs; }
    public function getCategories(): array { return $this->categories; }
    public function getStatistiques(): array { return $this->statistiques; }
    public function getSearch(): string { return $this->search; }
    public function getFilterRole(): string { return $this->filterRole; }
    public function hasAccess(): bool { return $this->checkAccess(); }
}
