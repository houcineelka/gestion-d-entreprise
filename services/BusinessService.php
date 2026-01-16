<?php
require_once __DIR__ . '/../config/Database.php';

/**
 * BusinessService - Fonctions métier de l'application
 */
class BusinessService {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Calculer la prime d'un commercial (10% du CA de l'année)
     */
    public function calculerPrimeCommercial($commercialId, $annee) {
        try {
            // Récupérer le CA total du commercial pour l'année
            // Le commercial peut être soit le commercial assigné au client, soit celui qui a passé la commande
            $sql = "SELECT COALESCE(SUM(c.montant_final), 0) as chiffre_affaire
                    FROM commandes c
                    JOIN clients cl ON c.client_id = cl.id
                    WHERE cl.commercial_id = :commercial_id
                    AND YEAR(c.date_commande) = :annee
                    AND c.statut IN ('validee', 'livree')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':commercial_id' => $commercialId,
                ':annee' => $annee
            ]);

            $result = $stmt->fetch();
            $chiffreAffaire = floatval($result['chiffre_affaire'] ?? 0);
            $tauxPrime = 10.0; // 10%
            $montantPrime = $chiffreAffaire * ($tauxPrime / 100);

            // Enregistrer la prime dans la base de données
            $this->enregistrerPrime($commercialId, $annee, $chiffreAffaire, $tauxPrime, $montantPrime);

            return [
                'commercial_id' => $commercialId,
                'annee' => $annee,
                'chiffre_affaire' => $chiffreAffaire,
                'taux_prime' => $tauxPrime,
                'montant_prime' => $montantPrime
            ];
        } catch (PDOException $e) {
            error_log("Erreur calcul prime: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculer les primes de tous les commerciaux pour une année
     */
    public function calculerToutesPrimes($annee) {
        try {
            // Récupérer tous les commerciaux
            $sql = "SELECT u.id, u.nom, u.prenom 
                    FROM utilisateurs u
                    JOIN categories_utilisateur cu ON u.categorie_id = cu.id
                    WHERE cu.nom = 'Commercial'";
            $stmt = $this->db->query($sql);
            $commerciaux = $stmt->fetchAll();

            $primes = [];
            foreach ($commerciaux as $commercial) {
                $prime = $this->calculerPrimeCommercial($commercial['id'], $annee);
                if ($prime) {
                    $prime['nom'] = $commercial['nom'];
                    $prime['prenom'] = $commercial['prenom'];
                    $primes[] = $prime;
                }
            }

            return $primes;
        } catch (PDOException $e) {
            error_log("Erreur calcul toutes primes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Enregistrer une prime dans la base de données
     */
    private function enregistrerPrime($commercialId, $annee, $chiffreAffaire, $tauxPrime, $montantPrime) {
        try {
            // Vérifier si une prime existe déjà pour ce commercial cette année
            $checkSql = "SELECT id FROM primes_commerciaux 
                         WHERE commercial_id = :commercial_id AND annee = :annee AND mois = 12";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([
                ':commercial_id' => $commercialId,
                ':annee' => $annee
            ]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Mise à jour
                $sql = "UPDATE primes_commerciaux 
                        SET chiffre_affaire = :chiffre_affaire,
                            taux_prime = :taux_prime,
                            montant_prime = :montant_prime,
                            date_calcul = CURRENT_TIMESTAMP
                        WHERE commercial_id = :commercial_id AND annee = :annee AND mois = 12";
            } else {
                // Insertion
                $sql = "INSERT INTO primes_commerciaux (commercial_id, annee, mois, chiffre_affaire, taux_prime, montant_prime)
                        VALUES (:commercial_id, :annee, 12, :chiffre_affaire, :taux_prime, :montant_prime)";
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':commercial_id' => $commercialId,
                ':annee' => $annee,
                ':chiffre_affaire' => $chiffreAffaire,
                ':taux_prime' => $tauxPrime,
                ':montant_prime' => $montantPrime
            ]);
        } catch (PDOException $e) {
            error_log("Erreur enregistrement prime: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer la remise pour un client (2.5% des achats de l'année)
     */
    public function calculerRemiseClient($clientId, $annee) {
        try {
            $sql = "SELECT COALESCE(SUM(montant_final), 0) as total_achats
                    FROM commandes
                    WHERE client_id = :client_id
                    AND YEAR(date_commande) = :annee
                    AND statut IN ('validee', 'livree')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':client_id' => $clientId,
                ':annee' => $annee
            ]);

            $result = $stmt->fetch();
            $totalAchats = floatval($result['total_achats'] ?? 0);
            $tauxRemise = 2.5; // 2.5%
            $montantRemise = $totalAchats * ($tauxRemise / 100);

            return [
                'client_id' => $clientId,
                'annee' => $annee,
                'total_achats' => $totalAchats,
                'taux_remise' => $tauxRemise,
                'montant_remise' => $montantRemise
            ];
        } catch (PDOException $e) {
            error_log("Erreur calcul remise: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Afficher la liste des primes accordées aux commerciaux
     */
    public function afficherListePrimes($annee = null) {
        try {
            $annee = $annee ?? date('Y');

            $sql = "SELECT p.*, u.nom, u.prenom, u.email
                    FROM primes_commerciaux p
                    JOIN utilisateurs u ON p.commercial_id = u.id
                    WHERE p.annee = :annee
                    ORDER BY p.montant_prime DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':annee' => $annee]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur affichage primes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Afficher la liste des clients avec le total de leurs achats et remises
     */
    public function afficherListeClientsAvecRemises($annee = null) {
        try {
            $annee = $annee ?? date('Y');

            $sql = "SELECT c.id, c.nom, c.prenom, c.email,
                           COUNT(cmd.id) as nb_commandes,
                           COALESCE(SUM(cmd.montant_final), 0) as total_achats,
                           COALESCE(SUM(cmd.montant_final), 0) * 0.025 as remise
                    FROM clients c
                    LEFT JOIN commandes cmd ON c.id = cmd.client_id
                        AND YEAR(cmd.date_commande) = :annee
                        AND cmd.statut IN ('validee', 'livree')
                    GROUP BY c.id, c.nom, c.prenom, c.email
                    ORDER BY total_achats DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':annee' => $annee]);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur affichage clients avec remises: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Générer un rapport PDF des primes
     */
    public function genererRapportPrimesPDF($annee) {
        $primes = $this->afficherListePrimes($annee);
        $clients = $this->afficherListeClientsAvecRemises($annee);

        $html = "<h1>Rapport des Primes et Remises - Année $annee</h1>";
        
        // Section Primes
        $html .= "<h2>Primes des Commerciaux (10% du CA)</h2>";
        $html .= "<table border='1' cellpadding='10'>";
        $html .= "<tr><th>Commercial</th><th>Email</th><th>Chiffre d'Affaire</th><th>Prime (10%)</th></tr>";

        $totalCA = 0;
        $totalPrimes = 0;
        foreach ($primes as $prime) {
            $totalCA += $prime['chiffre_affaire'];
            $totalPrimes += $prime['montant_prime'];
            $html .= "<tr>";
            $html .= "<td>{$prime['prenom']} {$prime['nom']}</td>";
            $html .= "<td>{$prime['email']}</td>";
            $html .= "<td>" . number_format($prime['chiffre_affaire'], 2, ',', ' ') . " €</td>";
            $html .= "<td>" . number_format($prime['montant_prime'], 2, ',', ' ') . " €</td>";
            $html .= "</tr>";
        }
        $html .= "<tr style='font-weight:bold;background:#f0f0f0;'>";
        $html .= "<td colspan='2'>TOTAL</td>";
        $html .= "<td>" . number_format($totalCA, 2, ',', ' ') . " €</td>";
        $html .= "<td>" . number_format($totalPrimes, 2, ',', ' ') . " €</td>";
        $html .= "</tr>";
        $html .= "</table>";

        // Section Remises
        $html .= "<h2>Remises des Clients (2,5% des achats)</h2>";
        $html .= "<table border='1' cellpadding='10'>";
        $html .= "<tr><th>Client</th><th>Email</th><th>Total Achats</th><th>Remise (2,5%)</th></tr>";

        $totalAchats = 0;
        $totalRemises = 0;
        foreach ($clients as $client) {
            if ($client['total_achats'] > 0) {
                $totalAchats += $client['total_achats'];
                $totalRemises += $client['remise'];
                $html .= "<tr>";
                $html .= "<td>{$client['prenom']} {$client['nom']}</td>";
                $html .= "<td>{$client['email']}</td>";
                $html .= "<td>" . number_format($client['total_achats'], 2, ',', ' ') . " €</td>";
                $html .= "<td>" . number_format($client['remise'], 2, ',', ' ') . " €</td>";
                $html .= "</tr>";
            }
        }
        $html .= "<tr style='font-weight:bold;background:#f0f0f0;'>";
        $html .= "<td colspan='2'>TOTAL</td>";
        $html .= "<td>" . number_format($totalAchats, 2, ',', ' ') . " €</td>";
        $html .= "<td>" . number_format($totalRemises, 2, ',', ' ') . " €</td>";
        $html .= "</tr>";
        $html .= "</table>";

        return $html;
    }
}