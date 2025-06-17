<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User; // Importation du modèle User
use Core\Validator;  // Importation de la classe Validator
use PDOException; // Pour la gestion des erreurs de base de données

/**
 * Contrôleur des utilisateurs.
 * Gère les opérations CRUD pour la ressource 'User'.
 */
class UserController extends Controller
{
    /**
     * @var User L'instance du modèle User.
     */
    protected User $userModel;

    /**
     * Constructeur du UserController.
     * Initialise le modèle User.
     */
    public function __construct()
    {
        // Appelle le constructeur parent pour initialiser $this->request et $this->response
        parent::__construct($this->request, $this->response);
        $this->userModel = new User();
    }

    /**
     * Récupère tous les utilisateurs.
     * Accessible via GET /users.
     *
     * @return void
     */
    public function getAll(): void
    {
        try {
            $users = $this->userModel->all();
            $this->response->success($users, 'Liste des utilisateurs récupérée avec succès.')->send();
        } catch (PDOException $e) {
            error_log("Database Error (getAll Users): " . $e->getMessage());
            $this->response->error('Erreur lors de la récupération des utilisateurs.', 500)->send();
        } catch (\Exception $e) {
            error_log("General Error (getAll Users): " . $e->getMessage());
            $this->response->error('Une erreur inattendue est survenue.', 500)->send();
        }
    }

    /**
     * Récupère un utilisateur par son ID.
     * Accessible via GET /users/:id.
     *
     * @return void
     */
    public function getOne(): void
    {
        // Récupère l'ID depuis les paramètres d'URI définis par le routeur
        $userId = $this->request->param('id');

        // Valide que l'ID est numérique
        if (!is_numeric($userId)) {
            $this->response->error('L\'ID utilisateur doit être un nombre valide.', 400)->send();
            return;
        }

        try {
            $user = $this->userModel->find((int)$userId);

            if (!$user) {
                $this->response->error('Utilisateur non trouvé.', 404)->send();
                return;
            }

            $this->response->success($user, 'Utilisateur récupéré avec succès.')->send();
        } catch (PDOException $e) {
            error_log("Database Error (getOne User): " . $e->getMessage());
            $this->response->error('Erreur lors de la récupération de l\'utilisateur.', 500)->send();
        } catch (\Exception $e) {
            error_log("General Error (getOne User): " . $e->getMessage());
            $this->response->error('Une erreur inattendue est survenue.', 500)->send();
        }
    }

    /**
     * Crée un nouvel utilisateur.
     * Accessible via POST /users.
     *
     * @return void
     */
    public function addNew(): void
    {
        $data = $this->request->getBody();

        $validator = new Validator();
        $rules = [
            'username' => 'required|alphanumeric|min:3|max:50',
            'email'    => 'required|email|max:255',
            'password' => 'required|min:6'
        ];

        if (!$validator->validate($data, $rules)) {
            $this->response->error('Erreurs de validation.', 422, $validator->errors())->send();
            return;
        }

        // Vérifier si l'email existe déjà
        try {
            if ($this->userModel->findByEmail($data['email'])) {
                $this->response->error('Cette adresse e-mail est déjà utilisée.', 409)->send(); // 409 Conflict
                return;
            }

            // Hachage du mot de passe avant de le sauvegarder
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            $newUserId = $this->userModel->create($data);

            if ($newUserId) {
                // Récupérer l'utilisateur nouvellement créé pour la réponse
                $newUser = $this->userModel->find($newUserId);
                // Ne pas renvoyer le mot de passe haché dans la réponse
                unset($newUser['password']);
                $this->response->success($newUser, 'Utilisateur créé avec succès.', 201)->send();
            } else {
                $this->response->error('Échec de la création de l\'utilisateur.', 500)->send();
            }
        } catch (PDOException $e) {
            error_log("Database Error (addNew User): " . $e->getMessage());
            $this->response->error('Erreur lors de la création de l\'utilisateur.', 500)->send();
        } catch (\Exception $e) {
            error_log("General Error (addNew User): " . $e->getMessage());
            $this->response->error('Une erreur inattendue est survenue.', 500)->send();
        }
    }

    /**
     * Met à jour un utilisateur existant.
     * Accessible via PUT /users/:id.
     *
     * @return void
     */
    public function update(): void
    {
        $userId = $this->request->param('id');
        $data = $this->request->getBody();

        if (!is_numeric($userId)) {
            $this->response->error('L\'ID utilisateur doit être un nombre valide.', 400)->send();
            return;
        }

        // Vérifie si des données sont fournies pour la mise à jour
        if (empty($data)) {
            $this->response->error('Aucune donnée fournie pour la mise à jour.', 400)->send();
            return;
        }

        $validator = new Validator();
        // Règles de validation pour la mise à jour (les champs ne sont pas tous obligatoires ici)
        $rules = [];
        if (isset($data['username'])) {
            $rules['username'] = 'alphanumeric|min:3|max:50';
        }
        if (isset($data['email'])) {
            $rules['email'] = 'email|max:255';
        }
        if (isset($data['password'])) {
            $rules['password'] = 'min:6';
        }

        if (!empty($rules) && !$validator->validate($data, $rules)) {
            $this->response->error('Erreurs de validation.', 422, $validator->errors())->send();
            return;
        }

        try {
            $existingUser = $this->userModel->find((int)$userId);
            if (!$existingUser) {
                $this->response->error('Utilisateur non trouvé.', 404)->send();
                return;
            }

            // Vérifier si l'email existe déjà pour un autre utilisateur (si l'email est mis à jour)
            if (isset($data['email']) && $data['email'] !== $existingUser['email']) {
                if ($this->userModel->findByEmail($data['email'])) {
                    $this->response->error('Cette adresse e-mail est déjà utilisée par un autre compte.', 409)->send();
                    return;
                }
            }

            // Hacher le nouveau mot de passe si fourni
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $updated = $this->userModel->update((int)$userId, $data);

            if ($updated) {
                $updatedUser = $this->userModel->find((int)$userId);
                unset($updatedUser['password']); // Ne pas renvoyer le mot de passe haché
                $this->response->success($updatedUser, 'Utilisateur mis à jour avec succès.')->send();
            } else {
                // Si update retourne faux, cela peut signifier qu'aucune ligne n'a été affectée
                // ce qui est le cas si les données sont identiques ou l'ID est introuvable (déjà géré)
                $this->response->error('Aucune modification à appliquer ou échec de la mise à jour.', 400)->send();
            }
        } catch (PDOException $e) {
            error_log("Database Error (update User): " . $e->getMessage());
            $this->response->error('Erreur lors de la mise à jour de l\'utilisateur.', 500)->send();
        } catch (\Exception $e) {
            error_log("General Error (update User): " . $e->getMessage());
            $this->response->error('Une erreur inattendue est survenue.', 500)->send();
        }
    }

    /**
     * Supprime un utilisateur.
     * Accessible via DELETE /users/:id.
     *
     * @return void
     */
    public function destroy(): void
    {
        $userId = $this->request->param('id');

        if (!is_numeric($userId)) {
            $this->response->error('L\'ID utilisateur doit être un nombre valide.', 400)->send();
            return;
        }

        try {
            $existingUser = $this->userModel->find((int)$userId);
            if (!$existingUser) {
                $this->response->error('Utilisateur non trouvé.', 404)->send();
                return;
            }

            $deleted = $this->userModel->delete((int)$userId);

            if ($deleted) {
                $this->response->success([], 'Utilisateur supprimé avec succès.', 200)->send();
            } else {
                $this->response->error('Échec de la suppression de l\'utilisateur.', 500)->send();
            }
        } catch (PDOException $e) {
            error_log("Database Error (destroy User): " . $e->getMessage());
            $this->response->error('Erreur lors de la suppression de l\'utilisateur.', 500)->send();
        } catch (\Exception $e) {
            error_log("General Error (destroy User): " . $e->getMessage());
            $this->response->error('Une erreur inattendue est survenue.', 500)->send();
        }
    }
}
