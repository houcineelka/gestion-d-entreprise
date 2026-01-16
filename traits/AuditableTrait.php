<?php
/**
 * Trait Auditable - Pour l'authentification et l'historique des actions
 */
trait AuditableTrait {

    /**
     * Vérifie si la table historique_actions existe
     */
    private function auditTableExists(): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $result = $db->query("SHOW TABLES LIKE 'historique_actions'");
            return $result->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Enregistre une action dans l'historique
     */
    protected function logAction($typeAction, $tableCible = null, $idCible = null, $details = null) {
        try {
            // Vérifier si la table existe
            if (!$this->auditTableExists()) {
                error_log("Table historique_actions n'existe pas. Veuillez exécuter le script schema.sql");
                return false;
            }

            $db = Database::getInstance()->getConnection();

            $sql = "INSERT INTO historique_actions
                    (utilisateur_id, type_action, table_cible, id_cible, details, ip_address)
                    VALUES (:utilisateur_id, :type_action, :table_cible, :id_cible, :details, :ip_address)";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':utilisateur_id' => $_SESSION['user_id'] ?? null,
                ':type_action' => $typeAction,
                ':table_cible' => $tableCible,
                ':id_cible' => $idCible,
                ':details' => $details,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            if (!$result) {
                error_log("Échec de l'insertion dans historique_actions: " . implode(', ', $stmt->errorInfo()));
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur lors de l'enregistrement de l'audit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    protected function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Vérifie si l'utilisateur a une catégorie spécifique
     */
    protected function hasRole($role) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }

    /**
     * Vérifie si l'utilisateur a l'une des catégories spécifiées
     */
    protected function hasAnyRole($roles) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $roles);
    }

    /**
     * Redirige vers la page de connexion si non authentifié
     */
    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('Location: ../pages/simple_interface_a_connexion.html');
            exit();
        }
    }

    /**
     * Récupère l'historique des actions d'un utilisateur
     */
    protected function getUserHistory($userId, $limit = 50) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "SELECT * FROM historique_actions
                    WHERE utilisateur_id = :user_id
                    ORDER BY date_action DESC
                    LIMIT :limit";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'historique: " . $e->getMessage());
            return [];
        }
    }
}
