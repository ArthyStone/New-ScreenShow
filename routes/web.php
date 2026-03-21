<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Controllers\QueueController;
// use App\Controllers\UploadController;
// use App\Controllers\AdminController;

$router = new Router();

$router->get('/login', [AuthController::class, 'redirectToTwitch']);
$router->get('/auth/twitch/callback', [AuthController::class, 'handleTwitchCallback']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/infos', [PageController::class, 'show', ['Infos']]); // utilise pas websocket mais utilise layout (default: false true)
$router->get('/images', [PageController::class, 'show', ['Images']], [
    'middleware' => 'auth'
]);
$router->get('/display', [PageController::class, 'show', ['Display', true, false]]); // utilise websocket mais pas layout
$router->get('/liste', [PageController::class, 'show', ['Liste', true]]); // utilise websocket




$router->post('/api/queue/add', [QueueController::class, 'add'], [
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