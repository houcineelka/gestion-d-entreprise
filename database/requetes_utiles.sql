-- Requêtes SQL Utiles pour l'Application DistribuMax

-- ====================================
-- CONSULTATION DES DONNÉES
-- ====================================

-- 1. Voir tous les utilisateurs avec leurs catégories
SELECT u.id, u.nom, u.prenom, u.email, c.nom as categorie, u.actif
FROM utilisateurs u
LEFT JOIN categories_utilisateur c ON u.categorie_id = c.id
ORDER BY u.nom;

-- 2. Voir tous les clients avec leur commercial
SELECT cl.id, cl.nom, cl.prenom, cl.email, cl.ville,
       CONCAT(u.prenom, ' ', u.nom) as commercial
FROM clients cl
LEFT JOIN utilisateurs u ON cl.commercial_id = u.id
ORDER BY cl.nom;

-- 3. Voir tous les produits avec leur statut de stock
SELECT id, nom, categorie, prix_unitaire, stock_actuel, stock_min,
       CASE
           WHEN stock_actuel <= 0 THEN 'RUPTURE'
           WHEN stock_actuel <= stock_min THEN 'ALERTE'
           ELSE 'OK'
       END as statut_stock
FROM produits
ORDER BY stock_actuel ASC;

-- 4. Voir toutes les commandes avec détails
SELECT c.id, c.date_commande,
       CONCAT(cl.prenom, ' ', cl.nom) as client,
       CONCAT(u.prenom, ' ', u.nom) as commercial,
       c.montant_final, c.statut
FROM commandes c
LEFT JOIN clients cl ON c.client_id = cl.id
LEFT JOIN utilisateurs u ON c.commercial_id = u.id
ORDER BY c.date_commande DESC;

-- 5. Voir les détails d'une commande spécifique
SELECT dc.*, p.nom as produit_nom, p.prix_unitaire as prix_actuel
FROM details_commande dc
LEFT JOIN produits p ON dc.produit_id = p.id
WHERE dc.commande_id = 1;

-- ====================================
-- STATISTIQUES
-- ====================================

-- 6. Nombre de clients par commercial
SELECT u.id, CONCAT(u.prenom, ' ', u.nom) as commercial,
       COUNT(cl.id) as nb_clients
FROM utilisateurs u
LEFT JOIN clients cl ON u.id = cl.commercial_id
WHERE u.categorie_id = (SELECT id FROM categories_utilisateur WHERE nom = 'Commercial')
GROUP BY u.id, u.prenom, u.nom
ORDER BY nb_clients DESC;

-- 7. Chiffre d'affaire par commercial (année courante)
SELECT u.id, CONCAT(u.prenom, ' ', u.nom) as commercial,
       COUNT(c.id) as nb_commandes,
       COALESCE(SUM(c.montant_final), 0) as chiffre_affaire
FROM utilisateurs u
LEFT JOIN commandes c ON u.id = c.commercial_id
    AND YEAR(c.date_commande) = YEAR(CURDATE())
    AND c.statut IN ('validee', 'livree')
WHERE u.categorie_id = (SELECT id FROM categories_utilisateur WHERE nom = 'Commercial')
GROUP BY u.id, u.prenom, u.nom
ORDER BY chiffre_affaire DESC;

-- 8. Top 10 des produits les plus vendus
SELECT p.id, p.nom, p.categorie,
       SUM(dc.quantite) as quantite_totale,
       SUM(dc.sous_total) as ca_total
FROM produits p
JOIN details_commande dc ON p.id = dc.produit_id
JOIN commandes c ON dc.commande_id = c.id
WHERE c.statut IN ('validee', 'livree')
GROUP BY p.id, p.nom, p.categorie
ORDER BY quantite_totale DESC
LIMIT 10;

-- 9. Top 10 des meilleurs clients (par montant d'achats)
SELECT cl.id, CONCAT(cl.prenom, ' ', cl.nom) as client,
       cl.email, cl.ville,
       COUNT(c.id) as nb_commandes,
       SUM(c.montant_final) as total_achats
FROM clients cl
JOIN commandes c ON cl.id = c.client_id
WHERE c.statut IN ('validee', 'livree')
GROUP BY cl.id, cl.prenom, cl.nom, cl.email, cl.ville
ORDER BY total_achats DESC
LIMIT 10;

-- 10. Statistiques globales du tableau de bord
SELECT
    (SELECT COUNT(*) FROM clients) as total_clients,
    (SELECT COUNT(*) FROM commandes) as total_commandes,
    (SELECT COUNT(*) FROM produits) as total_produits,
    (SELECT COUNT(*) FROM utilisateurs WHERE actif = 1) as total_utilisateurs,
    (SELECT COUNT(*) FROM produits WHERE stock_actuel <= stock_min) as produits_alerte,
    (SELECT SUM(montant_final) FROM commandes WHERE statut IN ('validee', 'livree')) as ca_total;

-- ====================================
-- CALCUL DES PRIMES
-- ====================================

-- 11. Calculer la prime d'un commercial pour une année
SELECT
    u.id,
    CONCAT(u.prenom, ' ', u.nom) as commercial,
    COALESCE(SUM(c.montant_final), 0) as chiffre_affaire,
    COALESCE(SUM(c.montant_final), 0) * 0.10 as prime_10_pourcent
FROM utilisateurs u
LEFT JOIN commandes c ON u.id = c.commercial_id
    AND YEAR(c.date_commande) = 2024
    AND c.statut IN ('validee', 'livree')
WHERE u.categorie_id = (SELECT id FROM categories_utilisateur WHERE nom = 'Commercial')
GROUP BY u.id, u.prenom, u.nom;

-- 12. Voir les primes enregistrées pour une année
SELECT
    CONCAT(u.prenom, ' ', u.nom) as commercial,
    u.email,
    p.annee,
    p.chiffre_affaire,
    p.taux_prime,
    p.montant_prime,
    p.date_calcul
FROM primes_commerciaux p
JOIN utilisateurs u ON p.commercial_id = u.id
WHERE p.annee = 2024
ORDER BY p.montant_prime DESC;

-- ====================================
-- CALCUL DES REMISES
-- ====================================

-- 13. Calculer la remise d'un client pour une année
SELECT
    cl.id,
    CONCAT(cl.prenom, ' ', cl.nom) as client,
    cl.email,
    COUNT(c.id) as nb_commandes,
    COALESCE(SUM(c.montant_final), 0) as total_achats,
    COALESCE(SUM(c.montant_final), 0) * 0.025 as remise_2_5_pourcent
FROM clients cl
LEFT JOIN commandes c ON cl.id = c.client_id
    AND YEAR(c.date_commande) = 2024
    AND c.statut IN ('validee', 'livree')
GROUP BY cl.id, cl.prenom, cl.nom, cl.email
HAVING total_achats > 0
ORDER BY total_achats DESC;

-- ====================================
-- HISTORIQUE ET AUDIT
-- ====================================

-- 14. Voir l'historique des actions d'un utilisateur
SELECT ha.*, CONCAT(u.prenom, ' ', u.nom) as utilisateur
FROM historique_actions ha
LEFT JOIN utilisateurs u ON ha.utilisateur_id = u.id
WHERE ha.utilisateur_id = 1
ORDER BY ha.date_action DESC
LIMIT 50;

-- 15. Voir toutes les connexions du jour
SELECT ha.*, CONCAT(u.prenom, ' ', u.nom) as utilisateur, u.email
FROM historique_actions ha
LEFT JOIN utilisateurs u ON ha.utilisateur_id = u.id
WHERE ha.type_action = 'LOGIN'
  AND DATE(ha.date_action) = CURDATE()
ORDER BY ha.date_action DESC;

-- 16. Actions les plus fréquentes
SELECT type_action, COUNT(*) as nombre
FROM historique_actions
GROUP BY type_action
ORDER BY nombre DESC;

-- ====================================
-- GESTION DES STOCKS
-- ====================================

-- 17. Produits en rupture de stock
SELECT * FROM produits
WHERE stock_actuel <= 0
ORDER BY nom;

-- 18. Produits en alerte (stock <= minimum)
SELECT * FROM produits
WHERE stock_actuel <= stock_min AND stock_actuel > 0
ORDER BY stock_actuel ASC;

-- 19. Valeur totale du stock
SELECT
    SUM(prix_unitaire * stock_actuel) as valeur_stock_total,
    COUNT(*) as nb_produits,
    SUM(stock_actuel) as quantite_totale
FROM produits;

-- 20. Valeur du stock par catégorie
SELECT
    categorie,
    COUNT(*) as nb_produits,
    SUM(stock_actuel) as quantite,
    SUM(prix_unitaire * stock_actuel) as valeur_stock
FROM produits
GROUP BY categorie
ORDER BY valeur_stock DESC;

-- ====================================
-- COMMANDES
-- ====================================

-- 21. Commandes par statut
SELECT statut, COUNT(*) as nombre, SUM(montant_final) as montant_total
FROM commandes
GROUP BY statut;

-- 22. Commandes du mois en cours
SELECT c.*, CONCAT(cl.prenom, ' ', cl.nom) as client
FROM commandes c
LEFT JOIN clients cl ON c.client_id = cl.id
WHERE MONTH(c.date_commande) = MONTH(CURDATE())
  AND YEAR(c.date_commande) = YEAR(CURDATE())
ORDER BY c.date_commande DESC;

-- 23. Commandes en attente depuis plus de 7 jours
SELECT c.*, CONCAT(cl.prenom, ' ', cl.nom) as client,
       DATEDIFF(CURDATE(), c.date_commande) as jours_attente
FROM commandes c
LEFT JOIN clients cl ON c.client_id = cl.id
WHERE c.statut = 'en_attente'
  AND DATEDIFF(CURDATE(), c.date_commande) > 7
ORDER BY c.date_commande ASC;

-- ====================================
-- NETTOYAGE ET MAINTENANCE
-- ====================================

-- 24. Supprimer l'historique de plus de 1 an
DELETE FROM historique_actions
WHERE date_action < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);

-- 25. Réinitialiser le mot de passe d'un utilisateur (admin123)
UPDATE utilisateurs
SET mot_de_passe = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@distribumax.com';

-- 26. Désactiver tous les utilisateurs inactifs depuis 6 mois
UPDATE utilisateurs u
SET actif = 0
WHERE NOT EXISTS (
    SELECT 1 FROM historique_actions ha
    WHERE ha.utilisateur_id = u.id
      AND ha.date_action > DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
);

-- ====================================
-- RAPPORTS AVANCÉS
-- ====================================

-- 27. Rapport mensuel des ventes (12 derniers mois)
SELECT
    DATE_FORMAT(date_commande, '%Y-%m') as mois,
    COUNT(*) as nb_commandes,
    SUM(montant_final) as ca_total,
    AVG(montant_final) as panier_moyen
FROM commandes
WHERE date_commande >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  AND statut IN ('validee', 'livree')
GROUP BY DATE_FORMAT(date_commande, '%Y-%m')
ORDER BY mois DESC;

-- 28. Taux de conversion (commandes livrées / total)
SELECT
    COUNT(*) as total_commandes,
    SUM(CASE WHEN statut = 'livree' THEN 1 ELSE 0 END) as commandes_livrees,
    SUM(CASE WHEN statut = 'annulee' THEN 1 ELSE 0 END) as commandes_annulees,
    ROUND(SUM(CASE WHEN statut = 'livree' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as taux_livraison
FROM commandes;

-- 29. Analyse ABC des produits (Pareto)
WITH ventes_produits AS (
    SELECT
        p.id,
        p.nom,
        SUM(dc.sous_total) as ca,
        SUM(SUM(dc.sous_total)) OVER () as ca_total
    FROM produits p
    JOIN details_commande dc ON p.id = dc.produit_id
    JOIN commandes c ON dc.commande_id = c.id
    WHERE c.statut IN ('validee', 'livree')
    GROUP BY p.id, p.nom
)
SELECT
    nom,
    ROUND(ca, 2) as chiffre_affaire,
    ROUND((ca / ca_total) * 100, 2) as pourcentage_ca,
    CASE
        WHEN (ca / ca_total) * 100 >= 70 THEN 'A'
        WHEN (ca / ca_total) * 100 >= 20 THEN 'B'
        ELSE 'C'
    END as categorie_abc
FROM ventes_produits
ORDER BY ca DESC;

-- 30. Clients inactifs (aucune commande depuis 6 mois)
SELECT
    cl.*,
    MAX(c.date_commande) as derniere_commande,
    DATEDIFF(CURDATE(), MAX(c.date_commande)) as jours_inactivite
FROM clients cl
LEFT JOIN commandes c ON cl.id = c.client_id
GROUP BY cl.id
HAVING derniere_commande IS NULL
    OR derniere_commande < DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
ORDER BY jours_inactivite DESC;
