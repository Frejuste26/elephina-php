<?php

namespace Core;

use Core\Request;
use Core\Response;

/**
 * Classe de base pour tous les middlewares de l'application.
 * Les middlewares permettent d'intercepter et de traiter les requêtes HTTP
 * avant qu'elles n'atteignent le contrôleur, ou après le traitement du contrôleur.
 */
abstract class Middleware
{
    /**
     * @var Request L'instance de la requête HTTP.
     */
    protected Request $request;

    /**
     * @var Response L'instance de la réponse HTTP.
     */
    protected Response $response;

    /**
     * Constructeur de la classe Middleware.
     * Injecte les instances de Request et Response.
     *
     * @param Request $request L'objet Request.
     * @param Response $response L'objet Response.
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Méthode abstraite que chaque middleware doit implémenter.
     * Cette méthode contient la logique du middleware et est appelée par le routeur.
     *
     * @return void
     */
    abstract public function handle(): void;
}
