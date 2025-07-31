<?php

namespace Core;

use PDO;
use PDOException;
use Exception; // Pour les exceptions générales

/**
 * Classe Database
 * Gère la connexion à la base de données via PDO.
 */
class Database
{
    /**
     * @var PDO L'instance de connexion PDO.
     */
    protected PDO $pdo;

    /**
     * @var array Les paramètres de configuration de la base de données.
     */
    protected array $config;

    /**
     * Constructeur de la classe Database.
     * Initialise la connexion PDO en utilisant les paramètres de configuration.
     *
     * @throws Exception Si la configuration de la base de données est manquante ou invalide.
     * @throws PDOException Si la connexion à la base de données échoue.
     */
    public function __construct()
    {
        // Chemin vers le fichier de configuration de la base de données
        $configPath = __DIR__ . '/../App/Configs/database.php';

        // Vérifier si le fichier de configuration existe
        if (!file_exists($configPath)) {
            throw new Exception("Database configuration file not found at: " . $configPath);
        }

        // Charger les paramètres de configuration de la base de données
        $this->config = require $configPath;

        // Valider les paramètres de configuration
        if (!isset($this->config['host'], $this->config['database'], $this->config['username'], $this->config['password'])) {
            throw new Exception("Invalid database configuration. Missing host, database, username, or password.");
        }

        $this->connect();
    }

    /**
     * Établit la connexion à la base de données en utilisant PDO.
     *
     * @throws PDOException Si la connexion échoue.
     */
    protected function connect(): void
    {
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Mode d'erreur pour lancer des exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Mode de récupération par défaut (tableau associatif)
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Désactiver l'émulation des requêtes préparées pour de meilleures performances et sécurité
        ];

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );
        } catch (PDOException $e) {
            // Log l'erreur et relance une exception plus générique pour éviter d'exposer les détails sensibles
            Logger::error("Database connection failed", ['error' => $e->getMessage()]);
            throw new PDOException("Impossible de se connecter à la base de données. Veuillez vérifier vos paramètres de connexion.");
        }
    }

    /**
     * Retourne l'instance PDO pour les interactions directes avec la base de données.
     *
     * @return PDO L'instance PDO.
     */
    public function getConnection(): PDO
    {
        // Assurez-vous que la connexion est toujours active
        if (!isset($this->pdo) || !$this->pdo) {
            $this->connect(); // Reconnecte si nécessaire
        }
        return $this->pdo;
    }
}
