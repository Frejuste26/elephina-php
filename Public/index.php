<?php

// Démarrer la session (si nécessaire pour votre application)
// session_start();

// Charger l'autoloader de Composer pour inclure automatiquement les classes
require_once __DIR__ . '/../vendor/autoload.php';

// Charger le fichier de configuration de la base de données
// (Assurez-vous que ce chemin est correct si vous déplacez le fichier config)
// $databaseConfig = require_once __DIR__ . '/../App/Configs/database.php';

// Inclure le fichier principal de l'application (Core/App.php)
use Core\App;

// Démarrer l'application Elephina
// Le constructeur de App gérera le routage et l'exécution de la requête
$app = new App();
$app->run();

