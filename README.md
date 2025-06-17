# 🐘 Elephina

> Un micro-framework PHP léger et performant conçu pour créer des API REST rapides. Ce framework offre toutes les fonctionnalités nécessaires pour développer des API sécurisées et évolutives avec une architecture claire et extensible.
Fonctionnalités

---

## 📛 Badges

![GitHub](https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-000000?style=for-the-badge&logo=composer&logoColor=white)

---

## 📚 Table des matières

- [🚀 Fonctionnalités Clés](#-fonctionnalités-clés)
- [🧱 Architecture du Projet](#-architecture-du-projet)
- [🧪 Installation Rapide](#-installation-rapide)
- [🌐 Exemple de Routes](#-exemple-de-routes)
- [🧪 Tests API (via cURL)](#-tests-api-via-curl)
- [📦 Réponses API Standardisées](#-réponses-api-standardisées)
- [⚙️ Prérequis](#️-prérequis)
- [🧠 Extensions Recommandées](#-extensions)
- [🤝 Contribuer](#-contribuer)
- [📄 Licence](#-licence)
- [📧 Contact](#-contact)
- [💡 Pourquoi le nom Elephina ?](#-pourquoi-le-nom-elephina-)

---

## 🚀 Fonctionnalités Clés

- ⚡ **Routeur rapide** : Support des méthodes `GET`, `POST`, `PUT`, `DELETE` avec gestion des routes dynamiques (`/users/:id`).
- 📥 **Requêtes & Réponses** : Traitement JSON standardisé avec en-têtes HTTP corrects.
- 🛡️ **Validation intégrée** : Règles de validation simples pour garantir l’intégrité des données entrantes.
- 🧩 **Middleware** : Authentification (JWT), CORS et autres couches personnalisables.
- 🗄️ **Modèles PDO** : Classe de base pour les interactions avec la base de données.
- ❗ **Gestion d'erreurs** : Réponses JSON homogènes (`400`, `401`, `404`, `422`, etc.).
- ⚙️ **Performances** : Sans dépendances lourdes, exécution rapide garantie.
- 🔐 **Sécurité prête à l’emploi** : Architecture pensée pour accueillir JWT, CORS, rate limiting, etc.

---

## 🧱 Architecture du Projet

Le framework suit une architecture MVC simplifiée, optimisée pour les API REST :
Elephina/
├── Public/ # Point d'entrée (index.php, .htaccess)
├── App/
│ ├── Controllers/ # Logique métier
│ ├── Middleware/ # Auth, CORS, etc.
│ ├── Models/ # Requêtes SQL via PDO
│ ├── Routes/ # Déclaration des routes
│ └── Configs/ # Configuration (BDD, clés, etc.)
├── Core/ # Cœur du framework
│ ├── App.php # Point d'entrée principal
│ ├── Router.php # Gestionnaire de routes
│ ├── Request.php # Analyse de la requête
│ ├── Response.php # Générateur de réponses JSON
│ ├── Controller.php # Classe de base
│ ├── Model.php # ORM simple via PDO
│ ├── Database.php # Connexion PDO
│ └── Validator.php # Validation des champs
├── composer.json
└── README.md

---

## 🧪 Installation Rapide

1. Clonez le dépôt :

   ```bash
   git clone https://github.com/Frejuste26/elephina-php.git

2. Installez les dépendances :

   ```bash
   composer install

3. Configurez votre serveur web pour pointer sur Public/ :

   Apache :
   <Directory /chemin/vers/elephina-php/Public>
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>

   Nginx :
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }

4. Créez une base de données MySQL nommée elephina avec une table users :

   ```sql
   CREATE DATABASE elephina;
   USE elephina;
   CREATE TABLE users (
       userId INT AUTO_INCREMENT PRIMARY KEY,
       username VARCHAR(255) NOT NULL,
       email VARCHAR(255) NOT NULL UNIQUE,
       password VARCHAR(255) NOT NULL
   );

5. Mettez à jour les paramètres de connexion dans App/Configs/database.php :

   ```php
   return [
       'host' => 'localhost',
       'database' => 'elephina',
       'username' => 'votre_utilisateur',
       'password' => 'votre_mot_de_passe'
   ];


---

## 🌐 Exemple de Routes

   ```php
    use Core\Router;

    Router::get('/users', 'UserController@GetAll', ['AuthMiddleware']);
    Router::get('/users/:id', 'UserController@GetOne', ['AuthMiddleware']);
    Router::post('/users', 'UserController@AddNew');
    Router::put('/users/:id', 'UserController@Update', ['AuthMiddleware']);
    Router::delete('/users/:id', 'UserController@Destroy', ['AuthMiddleware']);

```	

---

## 🧪 Tests API (via cURL)

   ```bash
        # Liste des utilisateurs
        curl -H "Authorization: Bearer votre_token" http://localhost/users

        # Détails d’un utilisateur
        curl -H "Authorization: Bearer votre_token" http://localhost/users/1

        # Création d’un utilisateur
        curl -X POST -H "Content-Type: application/json" \
        -d '{"name":"John","email":"john@example.com"}' \
        http://localhost/users

        # Mise à jour
        curl -X PUT -H "Authorization: Bearer votre_token" \
        -H "Content-Type: application/json" \
        -d '{"name":"John Updated"}' \
        http://localhost/users/1

        # Suppression
        curl -X DELETE -H "Authorization: Bearer votre_token" \
         http://localhost/users/1
	
```	

## 📦 Réponses API Standardisées

Réussite (200, 201) :

   ```json
    {
        "data": [...],
        "message": "Opération réussie"
    }

   ```

Erreur (400, 401, 404, 422) :

   ```json
    {
        "error": "Message d'erreur",
        "validation": {"champ": ["Erreur spécifique"]}
    }

   ```	

## ⚙️ Prérequis

- PHP >= 8.1
- Composer
- MySQL ou tout autre système de gestion de base de données compatible avec PDO
- Serveur web (Apache, Nginx, etc.)
- Postman, Insomnia, etc. pour tester les API

---

### 🧠 Extensions

Propose directement les packages :

```md
- 🔐 **JWT Auth** : [`firebase/php-jwt`](https://github.com/firebase/php-jwt)
- 📦 **CORS Middleware** : [`neomerx/cors-psr7`](https://github.com/llaville/cors-psr7)
- 📊 **Rate Limiting** : [`malkusch/lock`](https://github.com/malkusch/lock)
- 🔒 **Password Hashing** : [`paragonie/random_compat`](https://github.com/paragonie/random_compat)
```

---

## 🤝 Contribuer

1. Forkez le projet.
2. Créez une nouvelle branche : `git checkout -b feature/nouvelle-fonctionnalite`.
3. Faites vos modifications et commit : `git commit -m 'Ajout de la nouvelle fonctionnalité'`.
4. Poussez vers la branche : `git push origin feature/nouvelle-fonctionnalite`.
5. Ouvrez une Pull Request.

---

## 📄 Licence

![Licence](https://img.shields.io/github/license/Frejuste26/elephina-php?style=for-the-badge)
Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 📧 Contact

[![LinkedIn](https://img.shields.io/badge/LinkedIn-Frejuste-0077B5?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/frejuste)
[![GitHub](https://img.shields.io/badge/GitHub-Frejuste26-100000?style=for-the-badge&logo=github&logoColor=white)](https://github.com/Frejuste26)
[![Email](https://img.shields.io/badge/Email-Contact-black?style=for-the-badge&logo=gmail&logoColor=white)](mailto:frejuste26@gmail.com)


---

## 💡 Pourquoi le nom Elephina ?

Parce que c’est la grâce d’un éléphant dans un monde d’API : massif, stable, élégant, mais capable de danser avec rapidité quand il le faut. 🐘💃

---

> *"Build APIs like an elephant: strong, graceful, and unstoppable."*
