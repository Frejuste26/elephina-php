<?php

namespace Core;

use PDO;
use Core\Database;
use Exception;

/**
 * Classe de base pour tous les modèles de l'application.
 * Fournit des méthodes génériques pour les interactions avec la base de données.
 */
class Model
{
    /**
     * @var PDO L'instance de connexion PDO.
     */
    protected PDO $db;

    /**
     * @var string Le nom de la table associée à ce modèle.
     */
    protected string $table;

    /**
     * @var string La clé primaire de la table.
     */
    protected string $primaryKey = 'id'; // Clé primaire par défaut

    /**
     * Constructeur de la classe Model.
     * Initialise la connexion PDO via la classe Database.
     */
    public function __construct()
    {
        // Instancier la classe Database pour obtenir la connexion PDO
        $database = new Database();
        $this->db = $database->getConnection();

        // Si le nom de la table n'est pas explicitement défini dans un modèle enfant,
        // essayer de le déduire du nom de la classe.
        if (!isset($this->table)) {
            $className = (new \ReflectionClass($this))->getShortName();
            // Convertir 'UserModel' en 'users' par exemple
            $this->table = strtolower(preg_replace('/Model$/', '', $className)) . 's';
        }
    }

    /**
     * Récupère tous les enregistrements de la table associée.
     *
     * @return array Tableau d'enregistrements.
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un enregistrement par sa clé primaire.
     *
     * @param int|string $id La valeur de la clé primaire.
     * @return array|null L'enregistrement trouvé ou null si non trouvé.
     */
    public function find(int|string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Crée un nouvel enregistrement dans la table.
     *
     * @param array $data Les données de l'enregistrement à créer (clé => valeur).
     * @return int L'ID du dernier enregistrement inséré.
     * @throws Exception En cas d'échec de l'insertion.
     */
    public function create(array $data): int
    {
        if (empty($data)) {
            throw new Exception("No data provided for creation.");
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        if ($stmt->execute()) {
            return (int) $this->db->lastInsertId();
        }

        throw new Exception("Failed to create record in table {$this->table}.");
    }

    /**
     * Met à jour un enregistrement existant.
     *
     * @param int|string $id La valeur de la clé primaire de l'enregistrement à mettre à jour.
     * @param array $data Les données à mettre à jour (clé => valeur).
     * @return bool Vrai si la mise à jour a réussi, faux sinon.
     * @throws Exception En cas d'échec de la mise à jour ou de données vides.
     */
    public function update(int|string $id, array $data): bool
    {
        if (empty($data)) {
            throw new Exception("No data provided for update.");
        }

        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Supprime un enregistrement par sa clé primaire.
     *
     * @param int|string $id La valeur de la clé primaire de l'enregistrement à supprimer.
     * @return bool Vrai si la suppression a réussi, faux sinon.
     */
    public function delete(int|string $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Exécute une requête SQL personnalisée (SELECT).
     *
     * @param string $sql La requête SQL.
     * @param array $params Les paramètres à lier à la requête.
     * @return array Le tableau des résultats.
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Exécute une requête SQL personnalisée (INSERT, UPDATE, DELETE).
     *
     * @param string $sql La requête SQL.
     * @param array $params Les paramètres à lier à la requête.
     * @return int Le nombre de lignes affectées.
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
