-- Base de données pour l'application de gestion de grande distribution
-- Créer la base de données
CREATE DATABASE IF NOT EXISTS gestion_distribution CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_distribution;

-- Table des catégories d'utilisateurs
CREATE TABLE IF NOT EXISTS categories_utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Table des utilisateurs (Manager, Commercial, RH, etc.)
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    categorie_id INT NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories_utilisateur(id) ON DELETE RESTRICT,
    INDEX idx_email (email),
    INDEX idx_categorie (categorie_id)
) ENGINE=InnoDB;

-- Table des clients
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(20),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    commercial_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (commercial_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_commercial (commercial_id)
) ENGINE=InnoDB;

-- Table des produits
CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    prix_unitaire DECIMAL(10, 2) NOT NULL,
    stock_actuel INT DEFAULT 0,
    stock_min INT DEFAULT 10,
    categorie VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_stock (stock_actuel),
    INDEX idx_categorie (categorie)
) ENGINE=InnoDB;

-- Table des commandes
CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    commercial_id INT,
    montant_total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    remise_pourcentage DECIMAL(5, 2) DEFAULT 0,
    montant_final DECIMAL(10, 2) NOT NULL DEFAULT 0,
    statut ENUM('en_attente', 'validee', 'livree', 'annulee') DEFAULT 'en_attente',
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_livraison DATE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (commercial_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_client (client_id),
    INDEX idx_commercial (commercial_id),
    INDEX idx_statut (statut),
    INDEX idx_date (date_commande)
) ENGINE=InnoDB;

-- Table des détails de commandes
CREATE TABLE IF NOT EXISTS details_commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10, 2) NOT NULL,
    sous_total DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE RESTRICT,
    INDEX idx_commande (commande_id),
    INDEX idx_produit (produit_id)
) ENGINE=InnoDB;

-- Table historique/audit
CREATE TABLE IF NOT EXISTS historique_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    type_action VARCHAR(100) NOT NULL,
    table_cible VARCHAR(100),
    id_cible INT,
    details TEXT,
    ip_address VARCHAR(45),
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_utilisateur (utilisateur_id),
    INDEX idx_date (date_action),
    INDEX idx_type (type_action)
) ENGINE=InnoDB;

-- Table des primes des commerciaux
CREATE TABLE IF NOT EXISTS primes_commerciaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commercial_id INT NOT NULL,
    annee INT NOT NULL,
    mois INT NOT NULL,
    chiffre_affaire DECIMAL(12, 2) NOT NULL DEFAULT 0,
    taux_prime DECIMAL(5, 2) DEFAULT 10.00,
    montant_prime DECIMAL(10, 2) NOT NULL DEFAULT 0,
    date_calcul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commercial_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_commercial_periode (commercial_id, annee, mois),
    INDEX idx_periode (annee, mois)
) ENGINE=InnoDB;

-- Insertion des catégories d'utilisateurs par défaut
INSERT INTO categories_utilisateur (nom, description) VALUES
('Manager', 'Accès complet à toutes les fonctionnalités'),
('Commercial', 'Gestion des clients et commandes'),
('RH', 'Gestion des utilisateurs et primes'),
('Magasinier', 'Gestion des stocks et produits');

-- Insertion d'un utilisateur administrateur par défaut (mot de passe: admin123)
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, categorie_id) VALUES
('Admin', 'Système', 'admin@distribumax.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insertion de quelques produits de test
INSERT INTO produits (nom, description, prix_unitaire, stock_actuel, stock_min, categorie) VALUES
('Ordinateur portable HP', 'Ordinateur portable 15 pouces, 8GB RAM', 599.99, 50, 10, 'Informatique'),
('Souris sans fil Logitech', 'Souris ergonomique sans fil', 29.99, 150, 30, 'Accessoires'),
('Clavier mécanique', 'Clavier mécanique RGB', 89.99, 75, 20, 'Accessoires'),
('Écran 24 pouces', 'Écran Full HD IPS', 199.99, 40, 10, 'Informatique'),
('Casque audio', 'Casque audio avec microphone', 49.99, 100, 25, 'Accessoires');
