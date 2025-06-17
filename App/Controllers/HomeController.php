<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;

/**
 * Contrôleur de la page d'accueil/API racine.
 * Gère les requêtes vers les points d'entrée principaux de l'API.
 */
class HomeController extends Controller
{
    /**
     * Méthode d'index.
     * Renvoie un message de bienvenue pour le point d'entrée de l'API.
     * Accessible via GET / ou /api.
     *
     * @return void
     */
    public function index(): void
    {
        // Envoie une réponse de succès avec un message simple et un statut 200 OK.
        $this->response->success(
            ['status' => 'API is running'],
            'Bienvenue sur l\'API Elephina!'
        )->send();
    }
}
