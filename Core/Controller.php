<?php

namespace Core;

use Core\Request;
use Core\Response;

/**
 * Classe de base pour tous les contrôleurs de l'application.
 * Fournit un accès aux objets Request et Response.
 */
class Controller
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
     * Constructeur de la classe Controller.
     *
     * @param Request $request L'objet Request.
     * @param Response $response L'objet Response.
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
