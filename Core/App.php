<?php

namespace Core;

// Importation des classes nécessaires pour le fonctionnement de l'application
use Core\Router;
use Core\Request;
use Core\Response;
use Core\Config;
use Core\Logger;

/**
 * Classe principale de l'application Elephina.
 * Gère l'initialisation du routeur et le traitement des requêtes.
 */
class App
{
    /**
     * @var Router L'instance du routeur.
     */
    protected Router $router;

    /**
     * @var Request L'instance de la requête HTTP.
     */
    protected Request $request;

    /**
     * @var Response L'instance de la réponse HTTP.
     */
    protected Response $response;

    /**
     * Constructeur de la classe App.
     * Initialise la requête, la réponse et le routeur.
     */
    public function __construct()
    {
        // Charger la configuration
        Config::load();
        
        // Initialiser le logger
        Logger::init();
        
        // Initialisation de l'objet Request pour analyser la requête HTTP actuelle
        $this->request = new Request();
        // Initialisation de l'objet Response pour construire les réponses HTTP
        $this->response = new Response();
        // Initialisation de l'objet Router
        $this->router = new Router($this->request, $this->response);

        // Définir un gestionnaire d'erreurs global pour capturer les exceptions non capturées
        set_exception_handler([$this, 'handleException']);
        // Définir un gestionnaire d'erreurs PHP pour les erreurs non fatales
        set_error_handler([$this, 'handleError']);
    }

    /**
     * Méthode pour démarrer l'application.
     * Inclut les définitions de routes et exécute le routeur.
     */
    public function run(): void
    {
        // Inclure le fichier de définition des routes
        // Ce fichier contiendra toutes les routes de votre API
        $routesPath = __DIR__ . '/../App/Routes/api.php';
        if (file_exists($routesPath)) {
            require_once $routesPath;
        } else {
            // Gérer le cas où le fichier de routes n'existe pas
            $this->response->error('Routes file not found.', 500)->send();
            return;
        }

        // Dispatcher la requête via le routeur
        // Le routeur va trouver la route correspondante et exécuter l'action associée
        try {
            $this->router->dispatch();
        } catch (\Exception $e) {
            // Capturer les exceptions spécifiques au dispatching (ex: route non trouvée)
            $statusCode = $e->getCode() ?: 500;
            $this->response->error($e->getMessage(), $statusCode)->send();
        }
    }

    /**
     * Gestionnaire global pour les exceptions non capturées.
     *
     * @param \Throwable $exception L'exception à gérer.
     */
    public function handleException(\Throwable $exception): void
    {
        Logger::error("Unhandled exception", [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Déterminer le code de statut HTTP approprié
        $statusCode = $exception->getCode() >= 100 && $exception->getCode() < 600 ? $exception->getCode() : 500;

        // Si c'est une exception de type "route non trouvée" ou similaire
        if ($exception->getMessage() === 'Route not found' || $exception->getMessage() === 'Method not allowed') {
             $statusCode = 404; // Ou 405 pour méthode non autorisée
        }

        // Envoyer une réponse d'erreur JSON standardisée
        $this->response->error($exception->getMessage(), $statusCode)->send();
    }

    /**
     * Gestionnaire global pour les erreurs PHP.
     * Convertit les erreurs en exceptions pour une gestion centralisée.
     *
     * @param int $errno Le niveau de l'erreur.
     * @param string $errstr Le message d'erreur.
     * @param string $errfile Le nom du fichier où l'erreur a été détectée.
     * @param int $errline La ligne où l'erreur a été détectée.
     * @throws \ErrorException
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Pour les erreurs qui ne sont pas des E_NOTICE ou E_WARNING (qui pourraient être ignorées)
        if (!(error_reporting() & $errno)) {
            // Cette erreur n'est pas incluse dans error_reporting
            return false;
        }
        // Convertir l'erreur en ErrorException pour la capturer avec le gestionnaire d'exceptions
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}
