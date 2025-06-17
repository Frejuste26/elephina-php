<?php

// Importation de la classe Router du cœur du framework
use Core\Router;

/**
 * Ce fichier est utilisé pour définir toutes les routes de l'API de votre application.
 * Utilisez les méthodes statiques de la classe Router (get, post, put, delete)
 * pour enregistrer vos points de terminaison.
 *
 * Le format est: Router::{METHOD}('/votre-uri', 'NomDuControleur@nomDeLaMethode', ['NomDuMiddleware']);
 */

// Route de base pour vérifier si l'API fonctionne
// Accessible via GET / ou /api
Router::get('/', 'HomeController@index');
Router::get('/api', 'HomeController@index');


// --- Routes pour les utilisateurs (exemple tiré du README.md) ---

// Route pour obtenir la liste de tous les utilisateurs
// Cette route est protégée par AuthMiddleware, ce qui signifie qu'un jeton d'authentification est requis.
Router::get('/users', 'UserController@getAll', ['AuthMiddleware']);

// Route pour obtenir les détails d'un utilisateur spécifique par son ID
// Exemple d'URI dynamique: /users/123
Router::get('/users/:id', 'UserController@getOne', ['AuthMiddleware']);

// Route pour créer un nouvel utilisateur
// Cette route ne nécessite pas d'authentification pour l'enregistrement.
Router::post('/users', 'UserController@addNew');

// Route pour mettre à jour un utilisateur existant par son ID
// Cette route est protégée par AuthMiddleware.
Router::put('/users/:id', 'UserController@update', ['AuthMiddleware']);

// Route pour supprimer un utilisateur par son ID
// Cette route est protégée par AuthMiddleware.
Router::delete('/users/:id', 'UserController@destroy', ['AuthMiddleware']);


// --- Vous pouvez ajouter d'autres routes ici selon vos besoins ---

// Exemple: Route pour un produit
// Router::get('/products/:id', 'ProductController@getOne');
// Router::post('/products', 'ProductController@create', ['AuthMiddleware']);

// Exemple: Route pour la connexion (sans authentification préalable)
// Router::post('/auth/login', 'AuthController@login');

// N'oubliez pas que les noms des contrôleurs et des middlewares doivent correspondre
// aux noms de fichiers/classes réels dans App/Controllers et App/Middleware.

