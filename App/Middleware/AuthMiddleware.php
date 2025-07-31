<?php

namespace App\Middleware;

use Core\Middleware;
use Core\Config;
use Core\Logger;
use Exception;

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
            Logger::warning("Missing Authorization header", [
                'uri' => $this->request->getUri(),
                'method' => $this->request->getMethod()
            ]);
            $this->response->error('Accès non autorisé: Jeton d\'authentification manquant.', 401)->send();
            return; // Termine l'exécution du middleware et du script
        }

        // Le format attendu est "Bearer <token>"
        list($type, $token) = explode(' ', $authorizationHeader, 2);

        if (strtolower($type) !== 'bearer' || empty($token)) {
            // Si le format du jeton est incorrect, renvoyer une erreur 401
            Logger::warning("Invalid token format", [
                'authorization_header' => $authorizationHeader
            ]);
            $this->response->error('Accès non autorisé: Format de jeton invalide.', 401)->send();
            return;
        }

        // Validation du jeton
        try {
            if (!$this->validateToken($token)) {
                Logger::warning("Invalid or expired token", ['token_preview' => substr($token, 0, 10) . '...']);
                $this->response->error('Accès non autorisé: Jeton invalide ou expiré.', 401)->send();
                return;
            }
            
            Logger::info("Successful authentication", [
                'uri' => $this->request->getUri(),
                'method' => $this->request->getMethod()
            ]);
            
        } catch (Exception $e) {
            Logger::error("JWT validation error", [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 10) . '...'
            ]);
            $this->response->error('Accès non autorisé: ' . $e->getMessage(), 401)->send();
            return;
        }
    }

    /**
     * Valide un jeton JWT (version simplifiée pour la démo)
     * En production, utilisez une vraie bibliothèque JWT comme firebase/php-jwt
     *
     * @param string $token Le jeton à valider
     * @return bool True si le jeton est valide
     */
    protected function validateToken(string $token): bool
    {
        // Version simplifiée pour la démo
        // En production, remplacez par une vraie validation JWT
        $validTokens = [
            'demo_token_123',
            'test_token_456',
            Config::get('JWT_SECRET', 'default_secret')
        ];
        
        return in_array($token, $validTokens);
        
        // Exemple avec firebase/php-jwt (décommentez si vous l'installez):
        /*
        try {
            $secretKey = Config::get('JWT_SECRET');
            if (!$secretKey) {
                throw new Exception('JWT secret not configured');
            }
            
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            
            // Vérifier l'expiration
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return false;
            }
            
            // Stocker les informations utilisateur si nécessaire
            if (isset($decoded->user_id)) {
                // $this->request->setUserId($decoded->user_id);
            }
            
            return true;
        } catch (Exception $e) {
            Logger::error("JWT decode error", ['error' => $e->getMessage()]);
            return false;
        }
        */
    }
}

/**
 * Classe utilitaire pour la gestion des JWT (exemple)
 * En production, utilisez firebase/php-jwt
 */
class SimpleJWT
{
    /**
     * Génère un jeton simple (pour les tests)
     *
     * @param array $payload Les données à encoder
     * @param string $secret La clé secrète
     * @return string Le jeton généré
     */
    public static function encode(array $payload, string $secret): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    /**
     * Décode un jeton simple (pour les tests)
     *
     * @param string $token Le jeton à décoder
     * @param string $secret La clé secrète
     * @return array|null Les données décodées ou null si invalide
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if ($base64Signature !== $expectedSignature) {
            return null;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);
        
        // Vérifier l'expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
}
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
