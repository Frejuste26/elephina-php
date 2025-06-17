<?php

namespace Core;

use Core\Request;
use Core\Response;
use Exception;

/**
 * Classe Router
 * Gère l'enregistrement et le dispatching des routes.
 */
class Router
{
    /**
     * @var array Tableau associatif des routes enregistrées.
     * Chaque clé est une méthode HTTP (GET, POST, etc.), et la valeur est un tableau de routes.
     * Chaque route contient un motif (pattern), une action (controller@method) et des middlewares.
     */
    protected static array $routes = [];

    /**
     * @var Request L'instance de la requête HTTP.
     */
    protected Request $request;

    /**
     * @var Response L'instance de la réponse HTTP.
     */
    protected Response $response;

    /**
     * Constructeur du routeur.
     *
     * @param Request $request L'objet Request pour analyser la requête.
     * @param Response $response L'objet Response pour construire les réponses.
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Enregistre une route GET.
     *
     * @param string $uri Le motif de l'URI de la route.
     * @param string $action La chaîne "Controller@method" ou une closure.
     * @param array $middlewares Les noms des middlewares à appliquer à cette route.
     */
    public static function get(string $uri, string $action, array $middlewares = []): void
    {
        self::addRoute('GET', $uri, $action, $middlewares);
    }

    /**
     * Enregistre une route POST.
     *
     * @param string $uri Le motif de l'URI de la route.
     * @param string $action La chaîne "Controller@method" ou une closure.
     * @param array $middlewares Les noms des middlewares à appliquer à cette route.
     */
    public static function post(string $uri, string $action, array $middlewares = []): void
    {
        self::addRoute('POST', $uri, $action, $middlewares);
    }

    /**
     * Enregistre une route PUT.
     *
     * @param string $uri Le motif de l'URI de la route.
     * @param string $action La chaîne "Controller@method" ou une closure.
     * @param array $middlewares Les noms des middlewares à appliquer à cette route.
     */
    public static function put(string $uri, string $action, array $middlewares = []): void
    {
        self::addRoute('PUT', $uri, $action, $middlewares);
    }

    /**
     * Enregistre une route DELETE.
     *
     * @param string $uri Le motif de l'URI de la route.
     * @param string $action La chaîne "Controller@method" ou une closure.
     * @param array $middlewares Les noms des middlewares à appliquer à cette route.
     */
    public static function delete(string $uri, string $action, array $middlewares = []): void
    {
        self::addRoute('DELETE', $uri, $action, $middlewares);
    }

    /**
     * Ajoute une route au tableau des routes enregistrées.
     *
     * @param string $method La méthode HTTP (GET, POST, etc.).
     * @param string $uri Le motif de l'URI de la route.
     * @param string $action La chaîne "Controller@method" ou une closure.
     * @param array $middlewares Les noms des middlewares à appliquer à cette route.
     */
    protected static function addRoute(string $method, string $uri, string $action, array $middlewares): void
    {
        // Nettoyer l'URI pour retirer le slash final sauf pour la racine
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/'; // Assure que la racine est bien '/'
        }

        self::$routes[$method][] = [
            'pattern' => self::compileRoutePattern($uri), // Convertir en regex
            'uri' => $uri, // URI originale pour référence
            'action' => $action,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Compile le motif de l'URI en une expression régulière pour le matching.
     *
     * @param string $uri Le motif de l'URI.
     * @return string L'expression régulière compilée.
     */
    protected static function compileRoutePattern(string $uri): string
    {
        // Remplace les paramètres dynamiques (ex: :id) par une capture regex
        // Ex: /users/:id deviendra #^/users/([^/]+)$#
        $pattern = preg_replace('/:[a-zA-Z0-9_]+/', '([^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatche la requête : trouve la route correspondante et exécute l'action.
     *
     * @throws Exception Si la route n'est pas trouvée ou la méthode non autorisée.
     */
    public function dispatch(): void
    {
        $method = $this->request->getMethod();
        $uri = $this->request->getUri();
        $matchedRoute = null;
        $uriParams = [];

        // Itérer sur les routes pour la méthode HTTP actuelle
        foreach (self::$routes[$method] ?? [] as $route) {
            // Vérifier si l'URI correspond au motif de la route
            if (preg_match($route['pattern'], $uri, $matches)) {
                $matchedRoute = $route;
                // Extraire les paramètres de l'URI capturés par la regex
                array_shift($matches); // Supprimer la correspondance complète de l'URI
                $uriParams = $matches;
                break; // Une route a été trouvée, on arrête la recherche
            }
        }

        if (!$matchedRoute) {
            // Si aucune route n'est trouvée pour la méthode actuelle, vérifier si l'URI existe avec une autre méthode
            if ($this->isUriPresentInOtherMethods($uri, $method)) {
                throw new Exception('Method not allowed', 405);
            }
            throw new Exception('Route not found', 404);
        }

        // Définir les paramètres d'URI dans l'objet Request
        $this->request->setUriParams($uriParams);

        // Appliquer les middlewares avant d'exécuter l'action du contrôleur
        $this->applyMiddlewares($matchedRoute['middlewares']);

        // Exécuter l'action du contrôleur
        $this->executeAction($matchedRoute['action']);
    }

    /**
     * Vérifie si l'URI existe dans d'autres méthodes HTTP.
     * Utilisé pour renvoyer une erreur 405 (Method Not Allowed).
     *
     * @param string $uri L'URI de la requête.
     * @param string $currentMethod La méthode HTTP actuelle.
     * @return bool Vrai si l'URI est présente avec une autre méthode, faux sinon.
     */
    protected function isUriPresentInOtherMethods(string $uri, string $currentMethod): bool
    {
        foreach (self::$routes as $method => $routesForMethod) {
            if ($method === $currentMethod) {
                continue; // Ignorer la méthode actuelle
            }
            foreach ($routesForMethod as $route) {
                if (preg_match($route['pattern'], $uri)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Applique les middlewares définis pour la route.
     *
     * @param array $middlewares Les noms des middlewares à exécuter.
     * @throws Exception Si un middleware n'existe pas ou ne peut pas être exécuté.
     */
    protected function applyMiddlewares(array $middlewares): void
    {
        foreach ($middlewares as $middlewareName) {
            $middlewareClass = 'App\\Middleware\\' . $middlewareName;
            if (!class_exists($middlewareClass)) {
                throw new Exception("Middleware {$middlewareName} not found.", 500);
            }
            $middlewareInstance = new $middlewareClass($this->request, $this->response);

            if (!method_exists($middlewareInstance, 'handle')) {
                throw new Exception("Middleware {$middlewareName} must have a handle method.", 500);
            }
            // Exécuter le middleware. Si un middleware envoie une réponse, il terminera le script.
            $middlewareInstance->handle();
        }
    }

    /**
     * Exécute l'action du contrôleur associée à la route.
     *
     * @param string $action La chaîne "Controller@method".
     * @throws Exception Si le contrôleur ou la méthode n'existent pas.
     */
    protected function executeAction(string $action): void
    {
        list($controllerName, $methodName) = explode('@', $action);
        $controllerClass = 'App\\Controllers\\' . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller {$controllerName} not found.", 404);
        }

        $controllerInstance = new $controllerClass($this->request, $this->response);

        if (!method_exists($controllerInstance, $methodName)) {
            throw new Exception("Method {$methodName} not found in controller {$controllerName}.", 404);
        }

        // Exécuter la méthode du contrôleur, en lui passant l'objet Request si nécessaire
        // Pour un contrôleur RESTful, les données de Request seront accessibles via $this->request
        call_user_func([$controllerInstance, $methodName]);
    }
}
