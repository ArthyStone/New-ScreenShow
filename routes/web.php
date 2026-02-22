<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
// use App\Controllers\UploadController;
// use App\Controllers\AdminController;

$router = new Router();

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/
$router->get('/login', [AuthController::class, 'redirectToTwitch']);
$router->get('/auth/twitch/callback', [AuthController::class, 'handleTwitchCallback']);
$router->get('/logout', [AuthController::class, 'logout'], [
    'middleware' => 'auth'
]);

/*
|--------------------------------------------------------------------------
| Routes protégées
|--------------------------------------------------------------------------
*/
// $router->get('/upload', [UploadController::class, 'index'], [
//     'middleware' => 'permission:uploadImages'
// ]);

// $router->get('/admin/users', [AdminController::class, 'users'], [
//     'middleware' => 'permission:manageUsers'
// ]);

return $router;