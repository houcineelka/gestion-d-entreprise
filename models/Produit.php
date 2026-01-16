<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../traits/AuditableTrait.php';

/**
 * Classe Produit - Modèle pour la gestion des produits
 */
class Produit {
    use AuditableTrait;

    private $db;
    private $id;
    private $nom;
    private $description;
    private $prixUnitaire;
    private $stockActuel;
    private $stockMin;
    private $categorie;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Setters
    public function setNom($nom) { $this->nom = $nom; }
    public function setDescription($description) { $this->description = $description; }
    public function setPrixUnitaire($prix) { $this->prixUnitaire = $prix; }
    public function setStockActuel($stock) { $this->stockActuel = $stock; }
    public function setStockMin($stockMin) { $this->stockMin = $stockMin; }
    public function setCategorie($categorie) { $this->categorie = $categorie; }

    /**
     * Créer un nouveau produit
     */
    public function create() {
        try {
            $sql = "INSERT INTO produits (nom, description, prix_unitaire, stock_actuel, stock_min, categorie)
                    VALUES (:nom, :description, :prix_unitaire, :stock_actuel, :stock_min, :categorie)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom' => $this->nom,
                ':description' => $this->description,
                ':prix_unitaire' => $this->prixUnitaire,
                ':stock_actuel' => $this->stockActuel ?? 0,
                ':stock_min' => $this->stockMin ?? 10,
                ':categorie' => $this->categorie
            ]);

            $this->id = $this->db->lastInsertId();
            $this->logAction('CREATE_PRODUIT', 'produits', $this->id, "Nouveau produit: {$this->nom}");

            return true;
        } catch (PDOException $e) {
            error_log("Erreur création produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lire un produit par ID
     */
    public function read($id) {
        try {
            $sql = "SELECT * FROM produits WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lecture produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour un produit
     */
    public function update($id) {
        try {
            $sql = "UPDATE produits
                    SET nom = :nom, description = :description, prix_unitaire = :prix_unitaire,
                        stock_actuel = :stock_actuel, stock_min = :stock_min, categorie = :categorie
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':nom' => $this->nom,
                ':description' => $this->description,
                ':prix_unitaire' => $this->prixUnitaire,
                ':stock_actuel' => $this->stockActuel,
                ':stock_min' => $this->stockMin,
                ':categorie' => $this->categorie
            ]);

            if ($result) {
                $this->logAction('UPDATE_PRODUIT', 'produits', $id, "Modification produit: {$this->nom}");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur mise à jour produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un produit
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM produits WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            if ($result) {
                $this->logAction('DELETE_PRODUIT', 'produits', $id, "Suppression produit");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur suppression produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer tous les produits
     */
    public function getAll() {
        try {
            $sql = "SELECT * FROM produits ORDER BY nom";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur récupération produits: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les produits en rupture de stock
     */
    public function getProduitsEnRupture() {
        try {
            $sql = "SELECT * FROM produits WHERE stock_actuel <= stock_min ORDER BY stock_actuel";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur récupération produits en rupture: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour le stock (après une commande)
     */
    public function updateStock($produitId, $quantite) {
        try {
            $sql = "UPDATE produits SET stock_actuel = stock_actuel - :quantite WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $produitId, ':quantite' => $quantite]);

            if ($result) {
                $this->logAction('UPDATE_STOCK', 'produits', $produitId, "Stock -{$quantite}");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur mise à jour stock: " . $e->getMessage());
            return false;
        }
    }
}
