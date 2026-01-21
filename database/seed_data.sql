-- Fichier de données de test pour l'application
-- À exécuter APRÈS schema.sql

USE gestion_distribution;

-- Insertion d'utilisateurs supplémentaires
-- Mot de passe pour tous: password123

-- Commerciaux
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, categorie_id) VALUES
('Martin', 'Sophie', 'sophie.martin@distribumax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('Dubois', 'Pierre', 'pierre.dubois@distribumax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('Durand', 'Marie', 'marie.durand@distribumax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

-- RH
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, categorie_id) VALUES
('Lefebvre', 'Thomas', 'thomas.lefebvre@distribumax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);

-- Magasinier
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, categorie_id) VALUES
('Moreau', 'Julien', 'julien.moreau@distribumax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4);

-- Insertion de clients de test
INSERT INTO clients (nom, prenom, email, telephone, adresse, ville, code_postal, commercial_id) VALUES
('Bernard', 'Luc', 'luc.bernard@email.com', '0612345678', '12 Rue de la Paix', 'Paris', '75001', 2),
('Petit', 'Anne', 'anne.petit@email.com', '0623456789', '34 Avenue des Champs', 'Lyon', '69001', 2),
('Robert', 'Jean', 'jean.robert@email.com', '0634567890', '56 Boulevard Victor Hugo', 'Marseille', '13001', 3),
('Richard', 'Claire', 'claire.richard@email.com', '0645678901', '78 Rue du Commerce', 'Toulouse', '31000', 3),
('Dumont', 'Marc', 'marc.dumont@email.com', '0656789012', '90 Place de la République', 'Nice', '06000', 4),
('Laurent', 'Julie', 'julie.laurent@email.com', '0667890123', '23 Rue Nationale', 'Bordeaux', '33000', 4),
('Simon', 'Paul', 'paul.simon@email.com', '0678901234', '45 Avenue de la Liberté', 'Lille', '59000', 2),
('Michel', 'Emma', 'emma.michel@email.com', '0689012345', '67 Rue de Strasbourg', 'Nantes', '44000', 3),
('Lefebvre', 'Olivier', 'olivier.lefebvre2@email.com', '0690123456', '89 Boulevard de la Victoire', 'Rennes', '35000', 4),
('Leroy', 'Sarah', 'sarah.leroy@email.com', '0601234567', '12 Place du Marché', 'Reims', '51100', 2);

-- Insertion de produits supplémentaires
INSERT INTO produits (nom, description, prix_unitaire, stock_actuel, stock_min, categorie) VALUES
-- Informatique
('Ordinateur Dell XPS 13', 'Ultrabook 13 pouces, 16GB RAM, SSD 512GB', 1299.99, 30, 5, 'Informatique'),
('MacBook Pro 14', 'Laptop Apple M2, 16GB RAM, 512GB SSD', 2399.99, 15, 3, 'Informatique'),
('PC Gamer Asus ROG', 'PC gaming RTX 4070, 32GB RAM', 1899.99, 20, 5, 'Informatique'),
('Tablette Samsung Galaxy Tab', 'Tablette 11 pouces, 128GB', 449.99, 45, 10, 'Informatique'),
('iPad Pro 12.9', 'Tablette Apple M2, 256GB', 1299.99, 25, 5, 'Informatique'),

-- Accessoires
('Webcam Logitech HD', 'Webcam Full HD 1080p avec micro', 79.99, 100, 20, 'Accessoires'),
('Hub USB-C 7 ports', 'Hub avec HDMI, USB 3.0, lecteur SD', 49.99, 80, 15, 'Accessoires'),
('Tapis de souris XXL', 'Tapis de souris gaming 90x40cm', 24.99, 120, 30, 'Accessoires'),
('Support laptop', 'Support ergonomique réglable', 39.99, 60, 15, 'Accessoires'),
('Sac à dos laptop', 'Sac professionnel pour ordinateur 15 pouces', 69.99, 50, 10, 'Accessoires'),

-- Périphériques
('Imprimante HP LaserJet', 'Imprimante laser N&B, recto-verso', 299.99, 35, 8, 'Périphériques'),
('Scanner Canon', 'Scanner A4 haute résolution', 189.99, 25, 5, 'Périphériques'),
('Microphone Blue Yeti', 'Microphone USB professionnel', 129.99, 40, 10, 'Périphériques'),
('Caméra de sécurité', 'Caméra IP WiFi HD', 89.99, 70, 15, 'Périphériques'),

-- Réseau
('Routeur WiFi 6', 'Routeur TP-Link AX3000', 149.99, 55, 12, 'Réseau'),
('Switch Ethernet 8 ports', 'Switch Gigabit non managé', 39.99, 65, 15, 'Réseau'),
('Câble Ethernet Cat6 5m', 'Câble réseau haute vitesse', 12.99, 200, 50, 'Réseau'),
('Répéteur WiFi', 'Amplificateur de signal WiFi', 34.99, 90, 20, 'Réseau');

-- Insertion de commandes de test (pour calculer les primes)
-- Commandes pour le commercial Sophie Martin (ID: 2)
INSERT INTO commandes (client_id, commercial_id, montant_total, remise_pourcentage, montant_final, statut, date_commande, date_livraison) VALUES
(1, 2, 1299.99, 0, 1299.99, 'livree', '2024-01-15', '2024-01-20'),
(2, 2, 799.98, 0, 799.98, 'livree', '2024-02-10', '2024-02-15'),
(7, 2, 2399.99, 0, 2399.99, 'validee', '2024-03-05', '2024-03-10'),
(10, 2, 1499.97, 0, 1499.97, 'livree', '2024-04-20', '2024-04-25');

-- Commandes pour le commercial Pierre Dubois (ID: 3)
INSERT INTO commandes (client_id, commercial_id, montant_total, remise_pourcentage, montant_final, statut, date_commande, date_livraison) VALUES
(3, 3, 1899.99, 0, 1899.99, 'livree', '2024-01-25', '2024-01-30'),
(4, 3, 599.99, 0, 599.99, 'livree', '2024-02-28', '2024-03-05'),
(8, 3, 1299.99, 0, 1299.99, 'validee', '2024-05-15', '2024-05-20');

-- Commandes pour le commercial Marie Durand (ID: 4)
INSERT INTO commandes (client_id, commercial_id, montant_total, remise_pourcentage, montant_final, statut, date_commande, date_livraison) VALUES
(5, 4, 2399.99, 0, 2399.99, 'livree', '2024-02-14', '2024-02-19'),
(6, 4, 449.99, 0, 449.99, 'livree', '2024-03-22', '2024-03-27'),
(9, 4, 1899.99, 0, 1899.99, 'validee', '2024-06-10', '2024-06-15');

-- Insertion des détails de commandes
-- Détails pour commande 1
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(1, 6, 1, 1299.99, 1299.99);

-- Détails pour commande 2
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(2, 1, 1, 599.99, 599.99),
(2, 2, 1, 199.99, 199.99);

-- Détails pour commande 3
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(3, 7, 1, 2399.99, 2399.99);

-- Détails pour commande 4
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(4, 8, 1, 1899.99, 1899.99);

-- Détails pour commande 5
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(5, 8, 1, 1899.99, 1899.99);

-- Détails pour commande 6
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(6, 1, 1, 599.99, 599.99);

-- Détails pour commande 7
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(7, 6, 1, 1299.99, 1299.99);

-- Détails pour commande 8
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(8, 7, 1, 2399.99, 2399.99);

-- Détails pour commande 9
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(9, 9, 1, 449.99, 449.99);

-- Détails pour commande 10
INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire, sous_total) VALUES
(10, 8, 1, 1899.99, 1899.99);

-- Insertion d'actions dans l'historique
INSERT INTO audit (utilisateur_id, type_action, table_cible, id_cible, details, ip_address) VALUES
(1, 'LOGIN', 'utilisateurs', 1, 'Connexion admin', '127.0.0.1'),
(2, 'CREATE_CLIENT', 'clients', 1, 'Nouveau client: luc.bernard@email.com', '127.0.0.1'),
(2, 'CREATE_COMMANDE', 'commandes', 1, 'Nouvelle commande: 1299.99€', '127.0.0.1'),
(3, 'CREATE_CLIENT', 'clients', 3, 'Nouveau client: jean.robert@email.com', '127.0.0.1'),
(4, 'CREATE_CLIENT', 'clients', 5, 'Nouveau client: marc.dumont@email.com', '127.0.0.1');

-- Message de confirmation
SELECT 'Données de test insérées avec succès!' as message;
SELECT COUNT(*) as 'Nombre d\'utilisateurs' FROM utilisateurs;
SELECT COUNT(*) as 'Nombre de clients' FROM clients;
SELECT COUNT(*) as 'Nombre de produits' FROM produits;
SELECT COUNT(*) as 'Nombre de commandes' FROM commandes;
