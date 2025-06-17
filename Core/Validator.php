<?php

namespace Core;

/**
 * Classe Validator
 * Fournit des méthodes pour valider les données selon des règles définies.
 */
class Validator
{
    /**
     * @var array Tableau des erreurs de validation.
     */
    protected array $errors = [];

    /**
     * Valide un ensemble de données par rapport à un ensemble de règles.
     *
     * @param array $data Les données à valider (par exemple, $this->request->getBody()).
     * @param array $rules Les règles de validation. Format: ['champ' => 'règle1|règle2:paramètre|...'].
     * @return bool Vrai si toutes les données sont valides, faux sinon.
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = []; // Réinitialiser les erreurs avant chaque validation

        foreach ($rules as $field => $fieldRules) {
            // Sépare les règles par le caractère '|'
            $rulesArray = explode('|', $fieldRules);

            foreach ($rulesArray as $rule) {
                // Divise la règle et son paramètre éventuel (ex: "min:5")
                list($ruleName, $ruleParam) = array_pad(explode(':', $rule, 2), 2, null);

                // Récupère la valeur du champ à valider
                $value = $data[$field] ?? null; // Gère le cas où le champ n'est pas présent

                // Appelle la méthode de validation correspondante
                // Le nom de la méthode est "validate" + le nom de la règle en CamelCase
                $methodName = 'validate' . ucfirst($ruleName);

                if (method_exists($this, $methodName)) {
                    // Si la validation échoue, ajoute une erreur et passe à la règle suivante pour ce champ
                    if (!$this->$methodName($field, $value, $ruleParam)) {
                        break; // Arrête la validation pour ce champ si une règle échoue
                    }
                } else {
                    // Log une erreur si la méthode de validation n'existe pas
                    error_log("Validation rule method '{$methodName}' not found.");
                }
            }
        }
        return empty($this->errors); // Retourne vrai si aucune erreur n'a été ajoutée
    }

    /**
     * Retourne toutes les erreurs de validation.
     *
     * @return array Tableau des erreurs de validation (champ => [messages d'erreur]).
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Ajoute un message d'erreur pour un champ donné.
     *
     * @param string $field Le nom du champ.
     * @param string $message Le message d'erreur.
     */
    protected function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // --- Méthodes de validation spécifiques ---

    /**
     * Règle 'required': Vérifie si le champ n'est pas vide.
     *
     * @param string $field Le nom du champ.
     * @param mixed $value La valeur du champ.
     * @return bool
     */
    protected function validateRequired(string $field, mixed $value): bool
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (empty($value) && $value !== 0 && $value !== '0') { // 0 peut être une valeur valide
            $this->addError($field, "Le champ '{$field}' est obligatoire.");
            return false;
        }
        return true;
    }

    /**
     * Règle 'email': Vérifie si le champ est une adresse e-mail valide.
     *
     * @param string $field Le nom du champ.
     * @param mixed $value La valeur du champ.
     * @return bool
     */
    protected function validateEmail(string $field, mixed $value): bool
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "Le champ '{$field}' doit être une adresse e-mail valide.");
            return false;
        }
        return true;
    }

    /**
     * Règle 'min': Vérifie la longueur minimale d'une chaîne ou la valeur minimale d'un nombre.
     *
     * @param string $field Le nom du champ.
     * @param mixed $value La valeur du champ.
     * @param string $param Le paramètre de la règle (longueur/valeur minimale).
     * @return bool
     */
    protected function validateMin(string $field, mixed $value, ?string $param): bool
    {
        if ($param === null || !is_numeric($param)) {
            error_log("Validation rule 'min' requires a numeric parameter.");
            return true; // Ne pas échouer la validation à cause d'un paramètre manquant
        }
        $min = (int) $param;

        if (is_string($value) && strlen($value) < $min) {
            $this->addError($field, "Le champ '{$field}' doit contenir au moins {$min} caractères.");
            return false;
        } elseif (is_numeric($value) && $value < $min) {
            $this->addError($field, "Le champ '{$field}' doit être au moins {$min}.");
            return false;
        }
        return true;
    }

    /**
     * Règle 'max': Vérifie la longueur maximale d'une chaîne ou la valeur maximale d'un nombre.
     *
     * @param string $field Le nom du champ.
     * @param mixed $value La valeur du champ.
     * @param string $param Le paramètre de la règle (longueur/valeur maximale).
     * @return bool
     */
    protected function validateMax(string $field, mixed $value, ?string $param): bool
    {
        if ($param === null || !is_numeric($param)) {
            error_log("Validation rule 'max' requires a numeric parameter.");
            return true;
        }
        $max = (int) $param;

        if (is_string($value) && strlen($value) > $max) {
            $this->addError($field, "Le champ '{$field}' ne doit pas dépasser {$max} caractères.");
            return false;
        } elseif (is_numeric($value) && $value > $max) {
            $this->addError($field, "Le champ '{$field}' ne doit pas dépasser {$max}.");
            return false;
        }
        return true;
    }

    /**
     * Règle 'numeric': Vérifie si le champ est un nombre.
     *
     * @param string $field Le nom du champ.
     * @param mixed $value La valeur du champ.
     * @return bool
     */
    protected function validateNumeric(string $field, mixed $value): bool
    {
        if (!is_numeric($value)) {
            $this->addError($field, "Le champ '{$field}' doit être un nombre.");
            return false;
        }
        return true;
    }

    /**
     * Règle 'alpha': Vérifie si le champ contient uniquement des caractères alphabétiques.
     *
     * @param string $field Le nom du champ.
     * @param mixed $value La valeur du champ.
     * @return bool
     */
    protected function validateAlpha(string $field, mixed $value): bool
    {
        if (!is_string($value) || !preg_match('/^[a-zA-Z]+$/', $value)) {
            $this->addError($field, "Le champ '{$field}' ne doit contenir que des lettres.");
            return false;
        }
        return true;
    }

    /**
     * Règle 'alphanumeric': Vérifie si le champ contient uniquement des caractères alphanumériques.
     *
     * @param string $field Le nom du champ.
     * @param mixed $value La valeur du champ.
     * @return bool
     */
    protected function validateAlphanumeric(string $field, mixed $value): bool
    {
        if (!is_string($value) || !preg_match('/^[a-zA-Z0-9]+$/', $value)) {
            $this->addError($field, "Le champ '{$field}' ne doit contenir que des lettres et des chiffres.");
            return false;
        }
        return true;
    }

    /**
     * Règle 'equals': Vérifie si le champ est égal à un autre champ ou une valeur spécifique.
     *
     * @param string $field Le nom du champ.
     * @param mixed $value La valeur du champ.
     * @param string $param Le nom de l'autre champ ou la valeur à comparer.
     * @param array $data Toutes les données originales (nécessaires pour comparer avec un autre champ).
     * @return bool
     */
    protected function validateEquals(string $field, mixed $value, ?string $param, array $data): bool
    {
        // Si le paramètre est un autre champ (ex: 'password_confirmation')
        if (isset($data[$param])) {
            if ($value !== $data[$param]) {
                $this->addError($field, "Le champ '{$field}' ne correspond pas au champ '{$param}'.");
                return false;
            }
        } elseif ($value !== $param) { // Si le paramètre est une valeur littérale
            $this->addError($field, "Le champ '{$field}' doit être égal à '{$param}'.");
            return false;
        }
        return true;
    }
}
