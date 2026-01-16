<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../traits/AuditableTrait.php';

/**
 * Classe User - Modèle pour la gestion des utilisateurs
 */
class User {
    use AuditableTrait;

    private $db;
    private $id;
    private $nom;
    private $prenom;
    private $email;
    private $motDePasse;
    private $categorieId;
    private $actif;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNom() { return $this->nom; }
    public function getPrenom() { return $this->prenom; }
    public function getEmail() { return $this->email; }
    public function getCategorieId() { return $this->categorieId; }
    public function isActif() { return $this->actif; }

    // Setters
    public function setNom($nom) { $this->nom = $nom; }
    public function setPrenom($prenom) { $this->prenom = $prenom; }
    public function setEmail($email) { $this->email = $email; }
    public function setMotDePasse($password) { $this->motDePasse = password_hash($password, PASSWORD_DEFAULT); }
    public function setCategorieId($categorieId) { $this->categorieId = $categorieId; }
    public function setActif($actif) { $this->actif = $actif; }

    /**
     * Créer un nouvel utilisateur
     */
    public function create() {
        try {
            $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, categorie_id, actif)
                    VALUES (:nom, :prenom, :email, :mot_de_passe, :categorie_id, :actif)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom' => $this->nom,
                ':prenom' => $this->prenom,
                ':email' => $this->email,
                ':mot_de_passe' => $this->motDePasse,
                ':categorie_id' => $this->categorieId,
                ':actif' => $this->actif ?? true
            ]);

            $this->id = $this->db->lastInsertId();
            $this->logAction('CREATE_USER', 'utilisateurs', $this->id, "Nouvel utilisateur: {$this->email}");

            return true;
        } catch (PDOException $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lire un utilisateur par ID
     */
    public function read($id) {
        try {
            $sql = "SELECT u.*, c.nom as categorie_nom
                    FROM utilisateurs u
                    LEFT JOIN categories_utilisateur c ON u.categorie_id = c.id
                    WHERE u.id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();

            if ($row) {
                $this->id = $row['id'];
                $this->nom = $row['nom'];
                $this->prenom = $row['prenom'];
                $this->email = $row['email'];
                $this->categorieId = $row['categorie_id'];
                $this->actif = $row['actif'];
                return $row;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur lecture utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update($id) {
        try {
            $sql = "UPDATE utilisateurs
                    SET nom = :nom, prenom = :prenom, email = :email,
                        categorie_id = :categorie_id, actif = :actif
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':nom' => $this->nom,
                ':prenom' => $this->prenom,
                ':email' => $this->email,
                ':categorie_id' => $this->categorieId,
                ':actif' => $this->actif
            ]);

            if ($result) {
                $this->logAction('UPDATE_USER', 'utilisateurs', $id, "Modification utilisateur: {$this->email}");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur mise à jour utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un utilisateur (suppression logique)
     */
    public function delete($id) {
        try {
            $sql = "UPDATE utilisateurs SET actif = 0 WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            if ($result) {
                $this->logAction('DELETE_USER', 'utilisateurs', $id, "Désactivation utilisateur");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur suppression utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer tous les utilisateurs
     */
    public function getAll() {
        try {
            $sql = "SELECT u.*, c.nom as categorie_nom
                    FROM utilisateurs u
                    LEFT JOIN categories_utilisateur c ON u.categorie_id = c.id
                    ORDER BY u.nom, u.prenom";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur récupération utilisateurs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Authentifier un utilisateur
     */
    public function authenticate($email, $password) {
        try {
            $sql = "SELECT u.*, c.nom as categorie_nom
                    FROM utilisateurs u
                    LEFT JOIN categories_utilisateur c ON u.categorie_id = c.id
                    WHERE u.email = :email AND u.actif = 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                $this->logAction('LOGIN', 'utilisateurs', $user['id'], "Connexion réussie");
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur authentification: " . $e->getMessage());
            return false;
        }
    }
}
