<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use Core\Validator;
use Core\Config;
use Core\Logger;
use App\Middleware\SimpleJWT;
use PDOException;

/**
 * Contrôleur d'authentification.
 * Gère la connexion et l'inscription des utilisateurs.
 */
class AuthController extends Controller
{
    /**
     * @var User L'instance du modèle User.
     */
    protected User $userModel;

    /**
     * Constructeur du AuthController.
     */
    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Injecte les dépendances Request et Response.
     *
     * @param Request $request
     * @param Response $response
     */
    public function setDependencies($request, $response): void
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Connexion d'un utilisateur.
     * Accessible via POST /auth/login.
     *
     * @return void
     */
    public function login(): void
    {
        $data = $this->request->getBody();

        $validator = new Validator();
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];

        if (!$validator->validate($data, $rules)) {
            $this->response->error('Erreurs de validation.', 422, $validator->errors())->send();
            return;
        }

        try {
            $user = $this->userModel->findByEmail($data['email']);

            if (!$user || !password_verify($data['password'], $user['password'])) {
                Logger::warning("Failed login attempt", ['email' => $data['email']]);
                $this->response->error('Identifiants invalides.', 401)->send();
                return;
            }

            // Générer un jeton JWT
            $payload = [
                'user_id' => $user['userId'],
                'email' => $user['email'],
                'exp' => time() + (int)Config::get('JWT_EXPIRATION', 3600) // 1 heure par défaut
            ];

            $secret = Config::get('JWT_SECRET', 'default_secret');
            $token = SimpleJWT::encode($payload, $secret);

            Logger::info("Successful login", ['user_id' => $user['userId'], 'email' => $user['email']]);

            // Retourner les informations utilisateur sans le mot de passe
            unset($user['password']);
            
            $this->response->success([
                'user' => $user,
                'token' => $token,
                'expires_in' => (int)Config::get('JWT_EXPIRATION', 3600)
            ], 'Connexion réussie.')->send();

        } catch (PDOException $e) {
            Logger::error("Database error during login", ['error' => $e->getMessage()]);
            $this->response->error('Erreur lors de la connexion.', 500)->send();
        } catch (\Exception $e) {
            Logger::error("General error during login", ['error' => $e->getMessage()]);
            $this->response->error('Une erreur inattendue est survenue.', 500)->send();
        }
    }

    /**
     * Inscription d'un nouvel utilisateur.
     * Accessible via POST /auth/register.
     *
     * @return void
     */
    public function register(): void
    {
        $data = $this->request->getBody();

        $validator = new Validator();
        $rules = [
            'username' => 'required|alphanumeric|min:3|max:50',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6'
        ];

        if (!$validator->validate($data, $rules)) {
            $this->response->error('Erreurs de validation.', 422, $validator->errors())->send();
            return;
        }

        try {
            // Vérifier si l'email existe déjà
            if ($this->userModel->findByEmail($data['email'])) {
                $this->response->error('Cette adresse e-mail est déjà utilisée.', 409)->send();
                return;
            }

            // Hachage du mot de passe
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            $newUserId = $this->userModel->create($data);

            if ($newUserId) {
                $newUser = $this->userModel->find($newUserId);
                unset($newUser['password']);

                Logger::info("New user registered", ['user_id' => $newUserId, 'email' => $data['email']]);

                $this->response->success($newUser, 'Inscription réussie.', 201)->send();
            } else {
                $this->response->error('Échec de l\'inscription.', 500)->send();
            }

        } catch (PDOException $e) {
            Logger::error("Database error during registration", ['error' => $e->getMessage()]);
            $this->response->error('Erreur lors de l\'inscription.', 500)->send();
        } catch (\Exception $e) {
            Logger::error("General error during registration", ['error' => $e->getMessage()]);
            $this->response->error('Une erreur inattendue est survenue.', 500)->send();
        }
    }

    /**
     * Déconnexion d'un utilisateur (invalidation du token côté client).
     * Accessible via POST /auth/logout.
     *
     * @return void
     */
    public function logout(): void
    {
        // Dans une implémentation JWT stateless, la déconnexion se fait côté client
        // En supprimant le token du stockage local/session storage
        
        Logger::info("User logout", ['uri' => $this->request->getUri()]);
        
        $this->response->success([], 'Déconnexion réussie.')->send();
    }
}