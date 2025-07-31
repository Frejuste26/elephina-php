<?php

namespace App\Models;

use Core\Model;
use PDO;

/**
 * Modèle User
 * Gère les opérations de base de données pour la table 'users'.
 * Hérite de la classe Core\Model pour bénéficier des fonctionnalités CRUD génériques.
 */
class User extends Model
{
    /**
     * @var string Le nom de la table de base de données associée à ce modèle.
     * Définie explicitement pour ce modèle. Par défaut, elle serait déduite en 'users'.
     */
    protected string $table = 'users';

    /**
     * @var string La clé primaire de la table 'users'.
     * 'userId' est la clé primaire comme spécifié dans votre CREATE TABLE.
     */
    protected string $primaryKey = 'userId';

    /**
     * Constructeur du modèle User.
     * Appelle le constructeur parent pour initialiser la connexion PDO.
     */
    public function __construct()
    {
        parent::__construct(); // Appelle le constructeur de Core\Model
    }

    // Vous pouvez ajouter ici des méthodes spécifiques au modèle User si nécessaire,
    // par exemple, pour trouver un utilisateur par email, ou pour gérer les relations.

    /**
     * Récupère un utilisateur par son adresse e-mail.
     * Utile pour l'authentification ou la vérification d'existence.
     *
     * @param string $email L'adresse e-mail de l'utilisateur.
     * @return array|null L'enregistrement utilisateur ou null si non trouvé.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
}
