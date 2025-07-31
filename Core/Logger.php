<?php

namespace Core;

/**
 * Classe Logger
 * Système de logging simple pour l'application.
 */
class Logger
{
    /**
     * Niveaux de log
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * @var string Chemin du fichier de log
     */
    protected static string $logFile;

    /**
     * @var string Niveau de log minimum
     */
    protected static string $logLevel;

    /**
     * @var array Mapping des niveaux de log
     */
    protected static array $levels = [
        self::EMERGENCY => 0,
        self::ALERT => 1,
        self::CRITICAL => 2,
        self::ERROR => 3,
        self::WARNING => 4,
        self::NOTICE => 5,
        self::INFO => 6,
        self::DEBUG => 7,
    ];

    /**
     * Initialise le logger
     */
    public static function init(): void
    {
        self::$logFile = Config::get('LOG_FILE', __DIR__ . '/../logs/app.log');
        self::$logLevel = Config::get('LOG_LEVEL', self::ERROR);

        // Créer le répertoire de logs s'il n'existe pas
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Écrit un message de log
     *
     * @param string $level Niveau de log
     * @param string $message Message
     * @param array $context Contexte additionnel
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        if (!isset(self::$logFile)) {
            self::init();
        }

        // Vérifier si le niveau de log est suffisant
        if (self::$levels[$level] > self::$levels[self::$logLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log d'urgence
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log d'alerte
     */
    public static function alert(string $message, array $context = []): void
    {
        self::log(self::ALERT, $message, $context);
    }

    /**
     * Log critique
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Log d'erreur
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log d'avertissement
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log de notice
     */
    public static function notice(string $message, array $context = []): void
    {
        self::log(self::NOTICE, $message, $context);
    }

    /**
     * Log d'information
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log de debug
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }
}