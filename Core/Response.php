<?php

namespace Core;

/**
 * Classe Response
 * Gère la construction et l'envoi des réponses HTTP.
 */
class Response
{
    /**
     * @var int Le code de statut HTTP de la réponse.
     */
    protected int $statusCode = 200;

    /**
     * @var array Les en-têtes HTTP de la réponse.
     */
    protected array $headers = [];

    /**
     * @var mixed Le contenu de la réponse.
     */
    protected mixed $content = null;

    /**
     * Constructeur de la classe Response.
     * Initialise les en-têtes de base.
     */
    public function __construct()
    {
        // Définir l'en-tête Content-Type par défaut à application/json
        $this->setHeader('Content-Type', 'application/json');
    }

    /**
     * Définit le code de statut HTTP de la réponse.
     *
     * @param int $statusCode Le code de statut HTTP (ex: 200, 201, 404, 500).
     * @return self
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Retourne le code de statut HTTP actuel.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Définit un en-tête HTTP.
     *
     * @param string $key Le nom de l'en-tête.
     * @param string $value La valeur de l'en-tête.
     * @return self
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Retourne la valeur d'un en-tête spécifique ou tous les en-têtes.
     *
     * @param string|null $key Le nom de l'en-tête à récupérer.
     * @return string|array|null
     */
    public function getHeader(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->headers;
        }
        return $this->headers[$key] ?? null;
    }

    /**
     * Définit le contenu de la réponse.
     *
     * @param mixed $content Le contenu de la réponse.
     * @return self
     */
    public function setContent(mixed $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Retourne le contenu de la réponse.
     *
     * @return mixed
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Envoie une réponse JSON standardisée en cas de succès.
     *
     * @param array $data Les données à inclure dans la réponse.
     * @param string $message Un message descriptif.
     * @param int $statusCode Le code de statut HTTP (par défaut 200).
     * @return self
     */
    public function success(array $data = [], string $message = 'Opération réussie', int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setContent([
            'data' => $data,
            'message' => $message
        ]);
        return $this;
    }

    /**
     * Envoie une réponse JSON standardisée en cas d'erreur.
     *
     * @param string $error Le message d'erreur.
     * @param int $statusCode Le code de statut HTTP (par défaut 400).
     * @param array $validationErrors Les erreurs de validation (optionnel).
     * @return self
     */
    public function error(string $error, int $statusCode = 400, array $validationErrors = []): self
    {
        $this->setStatusCode($statusCode);
        $responseContent = ['error' => $error];
        if (!empty($validationErrors)) {
            $responseContent['validation'] = $validationErrors;
        }
        $this->setContent($responseContent);
        return $this;
    }

    /**
     * Envoie la réponse HTTP au client.
     * Envoie les en-têtes et le contenu.
     */
    public function send(): void
    {
        // Envoi du code de statut HTTP
        http_response_code($this->statusCode);

        // Envoi de tous les en-têtes définis
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }

        // Envoi du contenu, encodé en JSON
        echo json_encode($this->content);

        // Terminer l'exécution du script pour s'assurer qu'aucune autre sortie n'est envoyée
        exit();
    }
}
