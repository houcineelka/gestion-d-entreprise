<?php
/**
 * Script pour vérifier et créer la table historique_actions si elle n'existe pas
 * À exécuter une seule fois via le navigateur ou en ligne de commande
 */

require_once __DIR__ . '/../config/Database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Vérifier si la table existe
    $result = $db->query("SHOW TABLES LIKE 'historique_actions'");

    if ($result->rowCount() > 0) {
        echo "✓ La table 'historique_actions' existe déjà.\n";

        // Afficher le nombre d'enregistrements
        $count = $db->query("SELECT COUNT(*) as total FROM historique_actions")->fetch();
        echo "  - Nombre d'enregistrements: " . $count['total'] . "\n";
    } else {
        echo "✗ La table 'historique_actions' n'existe pas. Création en cours...\n";

        // Créer la table
        $sql = "CREATE TABLE IF NOT EXISTS historique_actions (
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
        ) ENGINE=InnoDB";

        $db->exec($sql);
        echo "✓ Table 'historique_actions' créée avec succès!\n";
    }

    // Tester l'insertion
    echo "\nTest d'insertion dans historique_actions...\n";

    $testSql = "INSERT INTO historique_actions (utilisateur_id, type_action, table_cible, details, ip_address)
                VALUES (NULL, 'TEST_AUDIT', 'test', 'Test de fonctionnement du système d\\'audit', '127.0.0.1')";

    if ($db->exec($testSql)) {
        echo "✓ Test d'insertion réussi!\n";

        // Supprimer l'entrée de test
        $db->exec("DELETE FROM historique_actions WHERE type_action = 'TEST_AUDIT'");
        echo "✓ Entrée de test supprimée.\n";
    }

    echo "\n========================================\n";
    echo "Le système d'audit est prêt à fonctionner!\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "\nAssurez-vous que:\n";
    echo "1. La base de données est accessible\n";
    echo "2. La table 'utilisateurs' existe (pour la clé étrangère)\n";
    echo "3. Les identifiants de connexion sont corrects dans config/Database.php\n";
}
