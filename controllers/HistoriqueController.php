<?php
/**
 * Contrôleur pour la gestion de l'historique des utilisateurs
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../traits/AuditableTrait.php';

class HistoriqueController {
    use AuditableTrait;
    
    private $db;
    private $historique = [];
    private $statistiques = [];
    private $utilisateurs = [];
    private $filterUser = null;
    private $filterAction = null;
    private $filterTable = null;
    private $dateDebut = null;
    private $dateFin = null;
    private $currentPage = 1;
    private $itemsPerPage = 20;
    private $totalPages = 1;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->requireAuth();
    }

    /**
     * Traite la requête HTTP
     */
    public function handleRequest() {
        // Récupérer les filtres
        $this->filterUser = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? intval($_GET['user_id']) : null;
        $this->filterAction = isset($_GET['action']) && $_GET['action'] !== '' ? $_GET['action'] : null;
        $this->filterTable = isset($_GET['table']) && $_GET['table'] !== '' ? $_GET['table'] : null;
        $this->dateDebut = isset($_GET['date_debut']) && $_GET['date_debut'] !== '' ? $_GET['date_debut'] : null;
        $this->dateFin = isset($_GET['date_fin']) && $_GET['date_fin'] !== '' ? $_GET['date_fin'] : null;
        $this->currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

        // Charger les données
        $this->loadUtilisateurs();
        $this->loadHistorique();
        $this->loadStatistiques();
    }

    /**
     * Charge la liste des utilisateurs pour les filtres
     */
    private function loadUtilisateurs() {
        try {
            $sql = "SELECT id, nom, prenom, email 
                    FROM utilisateurs 
                    WHERE actif = 1 
                    ORDER BY nom, prenom";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $this->utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement des utilisateurs: " . $e->getMessage());
            $this->utilisateurs = [];
        }
    }

    /**
     * Charge l'historique avec filtres et pagination
     */
    private function loadHistorique() {
        try {
            // Construire la requête de base - NOM CORRECT DE LA TABLE
            $sql = "SELECT 
                        h.*,
                        u.nom as utilisateur_nom,
                        u.prenom as utilisateur_prenom,
                        u.email as utilisateur_email
                    FROM historique_actions h
                    LEFT JOIN utilisateurs u ON h.utilisateur_id = u.id
                    WHERE 1=1";
            
            $params = [];

            // Appliquer les filtres
            if ($this->filterUser !== null) {
                $sql .= " AND h.utilisateur_id = :user_id";
                $params[':user_id'] = $this->filterUser;
            }

            if ($this->filterAction !== null) {
                $sql .= " AND h.type_action = :action";
                $params[':action'] = $this->filterAction;
            }

            if ($this->filterTable !== null) {
                $sql .= " AND h.table_cible = :table";
                $params[':table'] = $this->filterTable;
            }

            if ($this->dateDebut !== null) {
                $sql .= " AND DATE(h.date_action) >= :date_debut";
                $params[':date_debut'] = $this->dateDebut;
            }

            if ($this->dateFin !== null) {
                $sql .= " AND DATE(h.date_action) <= :date_fin";
                $params[':date_fin'] = $this->dateFin;
            }

            // Debug: Log de la requête
            error_log("SQL Historique: " . $sql);
            error_log("Params: " . print_r($params, true));

            // Compter le total pour la pagination
            $countSql = "SELECT COUNT(*) as total FROM historique_actions h WHERE 1=1";
            
            if ($this->filterUser !== null) {
                $countSql .= " AND h.utilisateur_id = :user_id";
            }
            if ($this->filterAction !== null) {
                $countSql .= " AND h.type_action = :action";
            }
            if ($this->filterTable !== null) {
                $countSql .= " AND h.table_cible = :table";
            }
            if ($this->dateDebut !== null) {
                $countSql .= " AND DATE(h.date_action) >= :date_debut";
            }
            if ($this->dateFin !== null) {
                $countSql .= " AND DATE(h.date_action) <= :date_fin";
            }

            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            error_log("Total historique trouvé: " . $total);
            
            $this->totalPages = max(1, ceil($total / $this->itemsPerPage));

            // Limiter la page courante
            if ($this->currentPage > $this->totalPages) {
                $this->currentPage = $this->totalPages;
            }

            // Ajouter l'ordre et la pagination
            $sql .= " ORDER BY h.date_action DESC";
            $offset = ($this->currentPage - 1) * $this->itemsPerPage;
            $sql .= " LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            // Bind des paramètres
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $this->historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

            error_log("Nombre d'entrées historique chargées: " . count($this->historique));

        } catch (PDOException $e) {
            error_log("Erreur lors du chargement de l'historique: " . $e->getMessage());
            error_log("SQL Error: " . print_r($e->errorInfo, true));
            $this->historique = [];
        }
    }

    /**
     * Charge les statistiques
     */
    private function loadStatistiques() {
        try {
            // Total des actions
            $sql = "SELECT COUNT(*) as total FROM historique_actions";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->statistiques['total_actions'] = $result ? $result['total'] : 0;

            // Actions aujourd'hui
            $sql = "SELECT COUNT(*) as total FROM historique_actions WHERE DATE(date_action) = CURDATE()";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->statistiques['today_actions'] = $result ? $result['total'] : 0;

            // Actions cette semaine
            $sql = "SELECT COUNT(*) as total FROM historique_actions WHERE YEARWEEK(date_action, 1) = YEARWEEK(CURDATE(), 1)";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->statistiques['week_actions'] = $result ? $result['total'] : 0;

            // Actions ce mois
            $sql = "SELECT COUNT(*) as total FROM historique_actions WHERE YEAR(date_action) = YEAR(CURDATE()) AND MONTH(date_action) = MONTH(CURDATE())";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->statistiques['month_actions'] = $result ? $result['total'] : 0;

            error_log("Statistiques chargées: " . print_r($this->statistiques, true));

        } catch (PDOException $e) {
            error_log("Erreur lors du chargement des statistiques: " . $e->getMessage());
            $this->statistiques = [
                'total_actions' => 0,
                'today_actions' => 0,
                'week_actions' => 0,
                'month_actions' => 0
            ];
        }
    }

    /**
     * Vérifie si l'utilisateur a accès à cette page
     */
    public function hasAccess() {
        return isset($_SESSION['user_role']) && 
               in_array($_SESSION['user_role'], ['Manager', 'RH']);
    }

    /**
     * Retourne l'icône pour un type d'action
     */
    public static function getActionIcon($action) {
        $icons = [
            'CREATE' => '➕',
            'UPDATE' => '✏️',
            'DELETE' => '🗑️',
            'LOGIN' => '🔐',
            'LOGOUT' => '🚪',
            'VIEW' => '👁️',
            'EXPORT' => '📥',
            'IMPORT' => '📤'
        ];
        return $icons[$action] ?? '📝';
    }

    /**
     * Retourne le label pour un type d'action
     */
    public static function getActionLabel($action) {
        $labels = [
            'CREATE' => 'Création',
            'UPDATE' => 'Modification',
            'DELETE' => 'Suppression',
            'LOGIN' => 'Connexion',
            'LOGOUT' => 'Déconnexion',
            'VIEW' => 'Consultation',
            'EXPORT' => 'Export',
            'IMPORT' => 'Import'
        ];
        return $labels[$action] ?? $action;
    }

    /**
     * Formate la description d'une action
     */
    public static function formatActionDescription($entry) {
        $user = htmlspecialchars($entry['utilisateur_prenom'] . ' ' . $entry['utilisateur_nom']);
        $action = self::getActionLabel($entry['type_action']);
        
        $description = "";
        
        switch ($entry['type_action']) {
            case 'CREATE':
                $description = "a créé un nouvel enregistrement";
                break;
            case 'UPDATE':
                $description = "a modifié un enregistrement";
                break;
            case 'DELETE':
                $description = "a supprimé un enregistrement";
                break;
            case 'LOGIN':
                $description = "s'est connecté(e) au système";
                break;
            case 'LOGOUT':
                $description = "s'est déconnecté(e) du système";
                break;
            case 'VIEW':
                $description = "a consulté un enregistrement";
                break;
            case 'EXPORT':
                $description = "a exporté des données";
                break;
            case 'IMPORT':
                $description = "a importé des données";
                break;
            default:
                $description = "a effectué une action";
        }

        if ($entry['table_cible']) {
            $description .= " dans la table <strong>" . htmlspecialchars($entry['table_cible']) . "</strong>";
        }

        return $description;
    }

    /**
     * Retourne les initiales d'un utilisateur
     */
    public static function getInitials($prenom, $nom) {
        $initiales = '';
        if ($prenom) {
            $initiales .= strtoupper(substr($prenom, 0, 1));
        }
        if ($nom) {
            $initiales .= strtoupper(substr($nom, 0, 1));
        }
        return $initiales ?: '??';
    }

    // Getters
    public function getHistorique() {
        return $this->historique;
    }

    public function getStatistiques() {
        return $this->statistiques;
    }

    public function getUtilisateurs() {
        return $this->utilisateurs;
    }

    public function getFilterUser() {
        return $this->filterUser;
    }

    public function getFilterAction() {
        return $this->filterAction;
    }

    public function getFilterTable() {
        return $this->filterTable;
    }

    public function getDateDebut() {
        return $this->dateDebut;
    }

    public function getDateFin() {
        return $this->dateFin;
    }

    public function getCurrentPage() {
        return $this->currentPage;
    }

    public function getTotalPages() {
        return $this->totalPages;
    }
}