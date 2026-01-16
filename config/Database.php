<?php
/**
 * Classe Database - Gestion de la connexion à la base de données (Pattern Singleton)
 */
class Database {
    private static $instance = null;
    private $connection;

    // Configuration de la base de données
    private $host = 'localhost';
    private $dbname = 'gestion_distribution';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';

    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    /**
     * Récupère l'instance unique de la connexion
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO de connexion
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup() {
        throw new Exception("Impossible de désérialiser un singleton");
    }
}
