<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../traits/AuditableTrait.php';

/**
 * Classe Client - Modèle pour la gestion des clients
 */
class Client {
    use AuditableTrait;

    private $db;
    private $id;
    private $nom;
    private $prenom;
    private $email;
    private $telephone;
    private $adresse;
    private $ville;
    private $codePostal;
    private $commercialId;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Getters et Setters
    public function getId() { return $this->id; }
    public function setNom($nom) { $this->nom = $nom; }
    public function setPrenom($prenom) { $this->prenom = $prenom; }
    public function setEmail($email) { $this->email = $email; }
    public function setTelephone($telephone) { $this->telephone = $telephone; }
    public function setAdresse($adresse) { $this->adresse = $adresse; }
    public function setVille($ville) { $this->ville = $ville; }
    public function setCodePostal($codePostal) { $this->codePostal = $codePostal; }
    public function setCommercialId($commercialId) { $this->commercialId = $commercialId; }

    /**
     * Créer un nouveau client
     */
    public function create() {
        try {
            $sql = "INSERT INTO clients (nom, prenom, email, telephone, adresse, ville, code_postal, commercial_id)
                    VALUES (:nom, :prenom, :email, :telephone, :adresse, :ville, :code_postal, :commercial_id)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nom' => $this->nom,
                ':prenom' => $this->prenom,
                ':email' => $this->email,
                ':telephone' => $this->telephone,
                ':adresse' => $this->adresse,
                ':ville' => $this->ville,
                ':code_postal' => $this->codePostal,
                ':commercial_id' => $this->commercialId
            ]);

            $this->id = $this->db->lastInsertId();
            $this->logAction('CREATE_CLIENT', 'clients', $this->id, "Nouveau client: {$this->email}");

            return true;
        } catch (PDOException $e) {
            error_log("Erreur création client: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lire un client par ID
     */
    public function read($id) {
        try {
            $sql = "SELECT c.*, CONCAT(u.prenom, ' ', u.nom) as commercial_nom
                    FROM clients c
                    LEFT JOIN utilisateurs u ON c.commercial_id = u.id
                    WHERE c.id = :id";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erreur lecture client: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour un client
     */
    public function update($id) {
        try {
            $sql = "UPDATE clients
                    SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
                        adresse = :adresse, ville = :ville, code_postal = :code_postal,
                        commercial_id = :commercial_id
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':nom' => $this->nom,
                ':prenom' => $this->prenom,
                ':email' => $this->email,
                ':telephone' => $this->telephone,
                ':adresse' => $this->adresse,
                ':ville' => $this->ville,
                ':code_postal' => $this->codePostal,
                ':commercial_id' => $this->commercialId
            ]);

            if ($result) {
                $this->logAction('UPDATE_CLIENT', 'clients', $id, "Modification client: {$this->email}");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur mise à jour client: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer un client
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM clients WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $id]);

            if ($result) {
                $this->logAction('DELETE_CLIENT', 'clients', $id, "Suppression client");
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur suppression client: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer tous les clients
     */
    public function getAll() {
        try {
            $sql = "SELECT c.*, CONCAT(u.prenom, ' ', u.nom) as commercial_nom
                    FROM clients c
                    LEFT JOIN utilisateurs u ON c.commercial_id = u.id
                    ORDER BY c.nom, c.prenom";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur récupération clients: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les clients d'un commercial
     */
    public function getByCommercial($commercialId) {
        try {
            $sql = "SELECT * FROM clients WHERE commercial_id = :commercial_id ORDER BY nom, prenom";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':commercial_id' => $commercialId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur récupération clients par commercial: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculer le total des achats d'un client sur une année
     */
    public function getTotalAchatsAnnee($clientId, $annee) {
        try {
            $sql = "SELECT SUM(montant_final) as total
                    FROM commandes
                    WHERE client_id = :client_id AND YEAR(date_commande) = :annee";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':client_id' => $clientId,
                ':annee' => $annee
            ]);

            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Erreur calcul total achats: " . $e->getMessage());
            return 0;
        }
    }
}
