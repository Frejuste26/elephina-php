<?php

namespace Core;

/**
 * Classe Request
 * Gère et encapsule les données de la requête HTTP entrante.
 */
class Request
{
    /**
     * @var string La méthode HTTP de la requête (GET, POST, PUT, DELETE).
     */
    protected string $method;

    /**
     * @var string L'URI de la requête.
     */
    protected string $uri;

    /**
     * @var array Les en-têtes de la requête.
     */
    protected array $headers;

    /**
     * @var array Le corps de la requête, généralement pour les requêtes POST/PUT.
     */
    protected array $body;

    /**
     * @var array Les paramètres de la requête (query parameters de l'URL).
     */
    protected array $queryParams;

    /**
     * @var array Les paramètres de l'URI extraits par le routeur (par exemple, /users/:id).
     */
    protected array $uriParams = [];

    /**
     * Constructeur de la classe Request.
     * Initialise les propriétés de la requête à partir des superglobales PHP.
     */
    public function __construct()
    {
        // Récupérer la méthode HTTP de la requête
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Récupérer l'URI de la requête et la nettoyer
        // Supprime les query string pour n'avoir que le chemin
        $this->uri = $this->parseUri($_SERVER['REQUEST_URI'] ?? '/');

        // Récupérer les en-têtes de la requête
        $this->headers = $this->getHeaders();

        // Récupérer les paramètres de la requête (query parameters)
        $this->queryParams = $_GET;

        // Récupérer le corps de la requête, principalement pour POST et PUT
        $this->body = $this->parseBody();
    }

    /**
     * Analyse l'URI de la requête pour en extraire le chemin.
     *
     * @param string $requestUri L'URI complète de la requête.
     * @return string Le chemin de l'URI sans les paramètres de requête.
     */
    protected function parseUri(string $requestUri): string
    {
        $uri = strtok($requestUri, '?');
        // Supprime les slashes multiples et s'assure qu'il commence par un slash
        $uri = preg_replace('#/+#', '/', $uri);
        return rtrim($uri, '/'); // Retire le slash final sauf si c'est la racine
    }

    /**
     * Récupère tous les en-têtes de la requête HTTP.
     *
     * @return array Les en-têtes de la requête.
     */
    protected function getHeaders(): array
    {
        if (function_exists('apache_request_headers')) {
            return apache_request_headers();
        }

        // Fallback pour les serveurs non Apache
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    /**
     * Analyse le corps de la requête en fonction du Content-Type.
     * Gère les données JSON et les données de formulaire standard.
     *
     * @return array Les données du corps de la requête.
     */
    protected function parseBody(): array
    {
        $body = [];
        $contentType = $this->getHeader('Content-Type');

        if ($this->method === 'POST' || $this->method === 'PUT' || $this->method === 'PATCH') {
            if (strpos($contentType, 'application/json') !== false) {
                // Si le Content-Type est JSON, décoder le flux d'entrée brut
                $input = file_get_contents('php://input');
                $decoded = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $body = $decoded;
                } else {
                    // Gérer l'erreur JSON si le corps n'est pas un JSON valide
                    error_log('Invalid JSON in request body: ' . $input);
                }
            } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false || strpos($contentType, 'multipart/form-data') !== false) {
                // Pour les formulaires classiques, les données sont dans $_POST
                $body = $_POST;
            }
            // Pour d'autres types de contenu ou si php://input est vide
            if (empty($body) && !empty($_POST)) {
                 $body = $_POST;
            }
        }
        return $body;
    }

    /**
     * Retourne la méthode HTTP de la requête.
     *
     * @return string La méthode HTTP.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Retourne l'URI de la requête.
     *
     * @return string L'URI.
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Retourne une valeur d'en-tête spécifique ou toutes les en-têtes.
     *
     * @param string|null $key Le nom de l'en-tête à récupérer (insensible à la casse).
     * @return string|array|null La valeur de l'en-tête, toutes les en-têtes, ou null si non trouvé.
     */
    public function getHeader(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->headers;
        }
        // Recherche insensible à la casse
        foreach ($this->headers as $name => $value) {
            if (strcasecmp($name, $key) === 0) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Retourne toutes les données du corps de la requête.
     *
     * @return array Les données du corps.
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Retourne une valeur spécifique du corps de la requête.
     *
     * @param string $key La clé à récupérer.
     * @param mixed $default La valeur par défaut si la clé n'existe pas.
     * @return mixed La valeur associée à la clé, ou la valeur par défaut.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Retourne tous les paramètres de la requête (query parameters).
     *
     * @return array Les paramètres de la requête.
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Retourne une valeur spécifique des paramètres de la requête.
     *
     * @param string $key La clé à récupérer.
     * @param mixed $default La valeur par défaut si la clé n'existe pas.
     * @return mixed La valeur associée à la clé, ou la valeur par défaut.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Définit les paramètres de l'URI extraits par le routeur.
     *
     * @param array $params Les paramètres de l'URI.
     */
    public function setUriParams(array $params): void
    {
        $this->uriParams = $params;
    }

    /**
     * Retourne les paramètres de l'URI extraits par le routeur.
     *
     * @return array Les paramètres de l'URI.
     */
    public function getUriParams(): array
    {
        return $this->uriParams;
    }

    /**
     * Retourne une valeur spécifique des paramètres de l'URI.
     *
     * @param string $key La clé à récupérer.
     * @param mixed $default La valeur par défaut si la clé n'existe pas.
     * @return mixed La valeur associée à la clé, ou la valeur par défaut.
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->uriParams[$key] ?? $default;
    }
}
