<?php

trait AuditableTrait {

  
    /**
     * Enregistre une action dans l'historique
     */
    protected function logAction($typeAction, $tableCible = null, $idCible = null, $details = null) {
        try {
            // S'assurer que la session est démarrée
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $db = Database::getInstance()->getConnection();

            // Récupérer l'ID utilisateur de la session
            $utilisateurId = $_SESSION['user_id'] ?? null;

            $sql = "INSERT INTO historique_actions
                    (utilisateur_id, type_action, table_cible, id_cible, details, ip_address)
                    VALUES (:utilisateur_id, :type_action, :table_cible, :id_cible, :details, :ip_address)";

            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':utilisateur_id' => $utilisateurId,
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
            error_log("Erreur lors de l'enregistrement de l'historique_actions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    protected function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Vérifie si l'utilisateur a une catégorie spécifique
     */
    protected function hasRole() {
        if (!$this->isAuthenticated()) {
            return false;
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === "Manager";
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
