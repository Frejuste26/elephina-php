<?php

namespace App\Middleware;

use Core\Middleware;
use Exception;
// Si vous utilisez JWT, vous devrez inclure la bibliothèque ici
// Par exemple: use Firebase\JWT\JWT;
// use Firebase\JWT\Key; // Pour PHP-JWT v6 et plus

/**
 * Middleware d'authentification.
 * Vérifie si la requête contient un jeton d'authentification valide.
 */
class AuthMiddleware extends Middleware
{
    /**
     * Gère la logique d'authentification.
     * Si l'authentification échoue, envoie une réponse d'erreur et termine l'exécution.
     *
     * @return void
     * @throws Exception Si l'authentification échoue.
     */
    public function handle(): void
    {
        // Récupérer l'en-tête Authorization
        $authorizationHeader = $this->request->getHeader('Authorization');

        if (!$authorizationHeader) {
            // Si l'en-tête Authorization est manquant, renvoyer une erreur 401
            $this->response->error('Accès non autorisé: Jeton d\'authentification manquant.', 401)->send();
            return; // Termine l'exécution du middleware et du script
        }

        // Le format attendu est "Bearer <token>"
        list($type, $token) = explode(' ', $authorizationHeader, 2);

        if (strtolower($type) !== 'bearer' || empty($token)) {
            // Si le format du jeton est incorrect, renvoyer une erreur 401
            $this->response->error('Accès non autorisé: Format de jeton invalide.', 401)->send();
            return;
        }

        // --- Logique de validation du jeton (à implémenter) ---
        // Cette partie dépendra de la bibliothèque JWT que vous utilisez (par exemple, firebase/php-jwt)
        // et de votre secret pour les jetons.

        // Exemple simplifié (vous devrez adapter ceci avec la vraie logique JWT):
        try {
            // Charger la clé secrète. En production, cette clé devrait être dans une variable d'environnement ou un fichier sécurisé.
            // Pour l'exemple, nous allons la définir ici.
            // $secretKey = 'your_super_secret_jwt_key'; // REMPLACEZ PAR UNE VRAIE CLÉ SECRÈTE !

            // Décoder le jeton. Ceci est un placeholder.
            // Si vous utilisez firebase/php-jwt (v6+):
            // $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));

            // Pour l'instant, simuler un jeton valide pour avancer.
            // En production, vous feriez une vraie validation (signature, expiration, etc.).
            if ($token === 'votre_token_secret_valide') {
                // Jeton valide. Vous pouvez stocker des informations de l'utilisateur
                // dans l'objet Request si nécessaire pour les contrôleurs.
                // Par exemple: $this->request->setUserId($decoded->userId);
                // Ou simplement laisser passer la requête.
            } else {
                // Jeton invalide ou expiré
                $this->response->error('Accès non autorisé: Jeton invalide ou expiré.', 401)->send();
                return;
            }

        } catch (Exception $e) {
            // Capturer les exceptions liées à la validation du jeton (ex: signature invalide, jeton expiré)
            error_log("JWT Validation Error: " . $e->getMessage());
            $this->response->error('Accès non autorisé: ' . $e->getMessage(), 401)->send();
            return;
        }

        // Si le jeton est valide, le middleware ne fait rien d'autre
        // et l'exécution du routeur continue vers l'action du contrôleur.
    }
}
