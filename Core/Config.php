<?php

namespace Core;

/**
 * Classe Config
 * Gère la configuration de l'application via des variables d'environnement.
 */
class Config
{
    /**
     * @var array Configuration chargée
     */
    protected static array $config = [];

    /**
     * @var bool Indique si la configuration a été chargée
     */
    protected static bool $loaded = false;

    /**
     * Charge la configuration depuis le fichier .env
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = __DIR__ . '/../App/Configs/.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue; // Ignorer les commentaires
                }
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Supprimer les guillemets si présents
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                }
                
                self::$config[$name] = $value;
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }

        // Charger la configuration de la base de données
        $dbConfigFile = __DIR__ . '/../App/Configs/database.php';
        if (file_exists($dbConfigFile)) {
            self::$config['database'] = require $dbConfigFile;
        }

        self::$loaded = true;
    }

    /**
     * Récupère une valeur de configuration
     *
     * @param string $key Clé de configuration
     * @param mixed $default Valeur par défaut
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config[$key] ?? $_ENV[$key] ?? $default;
    }

    /**
     * Définit une valeur de configuration
     *
     * @param string $key Clé de configuration
     * @param mixed $value Valeur
     */
    public static function set(string $key, mixed $value): void
    {
        self::$config[$key] = $value;
    }

    /**
     * Vérifie si une clé de configuration existe
     *
     * @param string $key Clé de configuration
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return isset(self::$config[$key]) || isset($_ENV[$key]);
    }
}