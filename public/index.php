<?php
// On empêche les conversions silencieuses.
// ça permet d'avoir des erreurs claires, au lieu de bugs difficiles à trouver.
declare(strict_types=1);
// On charge l'autoloader de Composer, qui va nous permettre de charger les classes automatiquement.
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\ErrorHandler;
ErrorHandler::register();

// On charge les variables d'environnement depuis le fichier .env
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
use App\Core\Router;
use App\Core\Session;
Session::start();

$router = require __DIR__ . '/../routes/web.php';
$router->dispatch();

/*
* composer install
* composer require vlucas/phpdotenv
* composer require mongodb/mongodb

* .env à la racine du projet :
MONGO_DSN= votre uri de connexion à MongoDB
MONGO_DB= votre nom de base de données
TWITCH_CLIENT_ID= votre client id de l'application Twitch
TWITCH_CLIENT_SECRET= votre client secret de l'application Twitch
TWITCH_REDIRECT_URI=http://localhost:8000/auth/twitch/callback
TAGS=["tag1","tag2","tag3","tag4","..."]
QUEUE_SERVER_POST=http://localhost:8080
QUEUE_SERVER_WEBSOCKET=ws://localhost:8080


* pour lancer en local : php -S localhost:8000 -t public
* pour lancer en prod on utilisera apache sur le vps.
*/