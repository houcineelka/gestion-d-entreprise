<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';

/**
 * PrimeController - Contrôleur pour la gestion des primes
 * Accessible par : Manager, RH
 */
class PrimeController {

    private $db;
    private $message = '';
    private $error = '';
    private $annee;
    private $listePrimes = [];
    private $listeClients = [];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->annee = $_GET['annee'] ?? date('Y');
    }

    /**
     * Vérifier les permissions d'accès
     */
    public function checkAccess(): bool {
        return in_array($_SESSION['user_role'] ?? '', ['Manager', 'RH']);
    }

    /**
     * Traiter la requête et préparer les données
     */
    public function handleRequest(): void {
      
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequest();
        }
        $this->loadData();
    }

    /**
     * Traiter les requêtes POST
     */
    private function handlePostRequest(): void {
        $postAction = $_POST['action'] ?? '';

        if ($postAction === 'calculer') {
            $anneeCalcul = $_POST['annee'] ?? date('Y');
            $primes = $this->calculerToutesPrimes($anneeCalcul);
            $this->message = 'Primes calculées et enregistrées pour l\'année ' . $anneeCalcul . ' : ' . count($primes) . ' commercial(aux) traité(s).';
        }
    }

    /**
     * Charger les données
     */
    private function loadData(): void {
        $this->listePrimes = $this->afficherListePrimes($this->annee);
        $this->listeClients = $this->afficherListeClientsAvecRemises($this->annee);
    }

    /**
     * Calculer la prime d'un commercial (10% du CA de l'année)
     */
    public function calculerPrimeCommercial($commercialId, $annee) {
        try {
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
            $tauxPrime = 10.0;
            $montantPrime = $chiffreAffaire * ($tauxPrime / 100);

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
            $checkSql = "SELECT id FROM primes_commerciaux
                         WHERE commercial_id = :commercial_id AND annee = :annee AND mois = 12";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([
                ':commercial_id' => $commercialId,
                ':annee' => $annee
            ]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                $sql = "UPDATE primes_commerciaux
                        SET chiffre_affaire = :chiffre_affaire,
                            taux_prime = :taux_prime,
                            montant_prime = :montant_prime,
                            date_calcul = CURRENT_TIMESTAMP
                        WHERE commercial_id = :commercial_id AND annee = :annee AND mois = 12";
            } else {
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
            $tauxRemise = 2.5;
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
     * Générer et télécharger un rapport PDF des primes
     */
    public function genererRapportPrimes($annee) {
        $primes = $this->afficherListePrimes($annee);
        $clients = $this->afficherListeClientsAvecRemises($annee);

        // Créer le PDF avec TCPDF (support Unicode/Arabe)
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Métadonnées du document
        $pdf->SetCreator('DistribuMax');
        $pdf->SetAuthor('DistribuMax');
        $pdf->SetTitle('Rapport des Primes et Remises - ' . $annee);

        // Désactiver l'en-tête et pied de page par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Marges
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->AddPage();

        // Police DejaVu pour le support Unicode
        $pdf->SetFont('dejavusans', 'B', 16);

        // Titre
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 15, 'Rapport des Primes et Remises - Année ' . $annee, 0, 1, 'C');
        $pdf->Ln(5);

        // Section Primes des Commerciaux
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->SetTextColor(76, 175, 80);
        $pdf->Cell(0, 10, 'Primes des Commerciaux (10% du CA)', 0, 1);

        // En-tête tableau primes
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(50, 8, 'Commercial', 1, 0, 'C', true);
        $pdf->Cell(60, 8, 'Email', 1, 0, 'C', true);
        $pdf->Cell(40, 8, "Chiffre d'Affaire", 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Prime (10%)', 1, 1, 'C', true);

        // Données primes
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $totalCA = 0;
        $totalPrimes = 0;
        $fill = false;

        foreach ($primes as $prime) {
            $totalCA += $prime['chiffre_affaire'];
            $totalPrimes += $prime['montant_prime'];

            if ($fill) {
                $pdf->SetFillColor(248, 249, 250);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            $pdf->Cell(50, 7, $prime['prenom'] . ' ' . $prime['nom'], 1, 0, 'L', true);
            $pdf->Cell(60, 7, $prime['email'], 1, 0, 'L', true);
            $pdf->Cell(40, 7, number_format($prime['chiffre_affaire'], 2, ',', ' ') . ' DH', 1, 0, 'R', true);
            $pdf->Cell(30, 7, number_format($prime['montant_prime'], 2, ',', ' ') . ' DH', 1, 1, 'R', true);
            $fill = !$fill;
        }

        // Total primes
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(110, 8, 'TOTAL', 1, 0, 'R', true);
        $pdf->Cell(40, 8, number_format($totalCA, 2, ',', ' ') . ' DH', 1, 0, 'R', true);
        $pdf->Cell(30, 8, number_format($totalPrimes, 2, ',', ' ') . ' DH', 1, 1, 'R', true);

        $pdf->Ln(10);

        // Section Remises des Clients
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->SetTextColor(76, 175, 80);
        $pdf->Cell(0, 10, 'Remises des Clients (2,5% des achats)', 0, 1);

        // En-tête tableau remises
        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(50, 8, 'Client', 1, 0, 'C', true);
        $pdf->Cell(60, 8, 'Email', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Total Achats', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Remise (2,5%)', 1, 1, 'C', true);

        // Données remises
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $totalAchats = 0;
        $totalRemises = 0;
        $fill = false;

        foreach ($clients as $client) {
            if ($client['total_achats'] > 0) {
                $totalAchats += $client['total_achats'];
                $totalRemises += $client['remise'];

                if ($fill) {
                    $pdf->SetFillColor(248, 249, 250);
                } else {
                    $pdf->SetFillColor(255, 255, 255);
                }
                $pdf->Cell(50, 7, $client['prenom'] . ' ' . $client['nom'], 1, 0, 'L', true);
                $pdf->Cell(60, 7, $client['email'], 1, 0, 'L', true);
                $pdf->Cell(40, 7, number_format($client['total_achats'], 2, ',', ' ') . ' DH', 1, 0, 'R', true);
                $pdf->Cell(30, 7, number_format($client['remise'], 2, ',', ' ') . ' DH', 1, 1, 'R', true);
                $fill = !$fill;
            }
        }

        // Total remises
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(110, 8, 'TOTAL', 1, 0, 'R', true);
        $pdf->Cell(40, 8, number_format($totalAchats, 2, ',', ' ') . ' DH', 1, 0, 'R', true);
        $pdf->Cell(30, 8, number_format($totalRemises, 2, ',', ' ') . ' DH', 1, 1, 'R', true);

        // Pied de page
        $pdf->Ln(15);
        $pdf->SetFont('dejavusans', 'I', 8);
        $pdf->SetTextColor(127, 140, 141);
        $pdf->Cell(0, 5, 'Rapport généré le ' . date('d/m/Y à H:i'), 0, 1, 'C');
        $pdf->Cell(0, 5, 'DistribuMax - Système de Gestion de Grande Distribution', 0, 1, 'C');

        // Téléchargement direct du PDF
        $pdf->Output('rapport_primes_' . $annee . '.pdf', 'D');
        exit;
    }

    /**
     * Filtrer les clients avec achats
     */
    public function getClientsAvecAchats(): array {
        return array_filter($this->listeClients, function($c) {
            return $c['nb_commandes'] > 0;
        });
    }

    /**
     * Calculer les totaux des primes
     */
    public function getTotauxPrimes(): array {
        $totalCA = 0;
        $totalPrimes = 0;

        foreach ($this->listePrimes as $prime) {
            $totalCA += $prime['chiffre_affaire'];
            $totalPrimes += $prime['montant_prime'];
        }

        return [
            'total_ca' => $totalCA,
            'total_primes' => $totalPrimes
        ];
    }

    /**
     * Calculer les totaux des remises
     */
    public function getTotauxRemises(): array {
        $clientsAvecAchats = $this->getClientsAvecAchats();
        $totalAchats = 0;
        $totalRemises = 0;

        foreach ($clientsAvecAchats as $client) {
            $totalAchats += $client['total_achats'];
            $totalRemises += $client['remise'];
        }

        return [
            'total_achats' => $totalAchats,
            'total_remises' => $totalRemises
        ];
    }

    // Getters pour la vue
    public function getMessage(): string { return $this->message; }
    public function getError(): string { return $this->error; }
    public function getAnnee(): string { return $this->annee; }
    public function getListePrimes(): array { return $this->listePrimes; }
    public function getListeClients(): array { return $this->listeClients; }
    public function hasAccess(): bool { return $this->checkAccess(); }
}
