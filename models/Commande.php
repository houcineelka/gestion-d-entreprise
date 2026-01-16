<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../traits/AuditableTrait.php';

/**
 * Classe Commande - Modèle pour la gestion des commandes
 */
class Commande {
    use AuditableTrait;

    private $db;
    private $id;
    private $clientId;
    private $commercialId;
    private $montantTotal;
    private $remisePourcentage;
    private $montantFinal;
    private $statut;
    private $dateLivraison;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Setters
    public function setClientId($clientId) { $this->clientId = $clientId; }
    public function setCommercialId($commercialId) { $this->commercialId = $commercialId; }
    public function setMontantTotal($montantTotal) { $this->montantTotal = $montantTotal; }
    public function setRemisePourcentage($remise) { $this->remisePourcentage = $remise; }
    public function setMontantFinal($montantFinal) { $this->montantFinal = $montantFinal; }
    public function setStatut($statut) { $this->statut = $statut; }
    public function setDateLivraison($date) { $this->dateLivraison = $date; }

    /**
     * Créer une nouvelle commande
     */
    public function create() {
        try {
            $sql = "INSERT INTO commandes
                    (client_id, commercial_id, montant_total, remise_pourcentage, montant_final, statut, date_livraison)
                    VALUES (:client_id, :commercial_id, :montant_total, :remise_pourcentage, :montant_final, :statut, :date_livraison)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':client_id' => $this->clientId,
                ':commercial_id' => $this->commercialId,
                ':montant_total' => $this->montantTotal,
                ':remise_pourcentage' => $this->remisePourcentage,
                ':montant_final' => $this->montantFinal,
                ':statut' => $this->statut ?? 'en_attente',
                ':date_livraison' => $this->dateLivraison
            ]);

            $this->id = $this->db->lastInsertId();
            $this->logAction('CREATE_COMMANDE', 'commandes', $this->id, "Nouvelle commande: {$this->montantFinal}€");

            return $this->id;
        } catch (PDOException $e) {
            error_log("Erreur création commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajouter un détail de commande
     */
    public function addDetail($commandeId, $produitId, $quantite, $prixUnitaire) {
        try {
            $sousTotal = $quantite * $prixUnitaire;

            $sql = "INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total)
                    VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire, :sous_total)";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':commande_id' => $commandeId,
                ':produit_id' => $produitId,
                ':quantite' => $quantite,
                ':prix_unitaire' => $prixUnitaire,
                ':sous_total' => $sousTotal
            ]);
        } catch (PDOException $e) {
            error_log("Erreur ajout détail commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lire une commande par ID
     */
    public function read($id) {
        try {
            $sql = "SELECT c.*,
                           CONCAT(cl.prenom, ' ', cl.nom) as client_nom,
                           cl.email as client_email,
                           CONCAT(u.prenom, ' ', u.nom) as commercial_nom
                    FROM commandes c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN utilisateurs u ON c.commercial_id = u.id
                    WHERE c.id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lecture commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les détails d'une commande
     */
    public function getDetails($commandeId) {
        try {
            $sql = "SELECT d.*, p.nom as produit_nom
                    FROM details_commande d
                    LEFT JOIN produits p ON d.produit_id = p.id
                    WHERE d.commande_id = :commande_id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':commande_id' => $commandeId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur récupération détails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateStatut($id, $statut) {
        try {
            $sql = "UPDATE commandes SET statut = :statut WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $id, ':statut' => $statut]);

            if ($result) {
                $this->logAction('UPDATE_COMMANDE_STATUT', 'commandes', $id, "Statut: {$statut}");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur mise à jour statut: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer toutes les commandes
     */
    public function getAll() {
        try {
            $sql = "SELECT c.*,
                           CONCAT(cl.prenom, ' ', cl.nom) as client_nom,
                           CONCAT(u.prenom, ' ', u.nom) as commercial_nom
                    FROM commandes c
                    LEFT JOIN clients cl ON c.client_id = cl.id
                    LEFT JOIN utilisateurs u ON c.commercial_id = u.id
                    ORDER BY c.date_commande DESC";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur récupération commandes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculer la remise pour un client (2.5% des achats de l'année)
     */
    public function calculerRemiseClient($clientId, $annee) {
        try {
            $sql = "SELECT SUM(montant_final) as total_achats
                    FROM commandes
                    WHERE client_id = :client_id AND YEAR(date_commande) = :annee";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':client_id' => $clientId, ':annee' => $annee]);

            $result = $stmt->fetch();
            $totalAchats = $result['total_achats'] ?? 0;

            return $totalAchats * 0.025; // 2.5%
        } catch (PDOException $e) {
            error_log("Erreur calcul remise: " . $e->getMessage());
            return 0;
        }
    }
}
