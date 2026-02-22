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





// Voici toutes les commandes à exécuter:
// composer require vlucas/phpdotenv
// composer require mongodb/mongodb



// 1. Installer les dépendances avec Composer :
//    composer install
// 2. Créer un fichier .env à la racine du projet avec les variables suivantes :
//    MONGO_DSN=mongodb+srv://<username>:<password>@cluster0.893moaf.mongodb.net/
//    MONGO_DB=Screenshow
// 3. Lancer le serveur de développement intégré de PHP :
//    php -S localhost:8000 -t public
// 4. Accéder à l'application dans votre navigateur :
//    http://localhost:8000