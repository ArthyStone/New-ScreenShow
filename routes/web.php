<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Controllers\QueueController;
use App\Controllers\CouponController;
// use App\Controllers\UploadController;
// use App\Controllers\AdminController;

$router = new Router();

$router->get('/login', [AuthController::class, 'redirectToTwitch']);
$router->get('/auth/twitch/callback', [AuthController::class, 'handleTwitchCallback']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/', [PageController::class, 'redirect', ['Infos']]);
$router->get('/i', [PageController::class, 'redirect', ['Infos']]);
$router->get('/info', [PageController::class, 'redirect', ['Infos']]);
$router->get('/infos', [PageController::class, 'show', ['Infos']]); // utilise pas websocket mais utilise layout (default: false true)
$router->get('/images', [PageController::class, 'show', ['Images']], [
    'middleware' => 'auth'
]);
$router->get('/display', [PageController::class, 'show', ['Display', true, false]]); // utilise websocket mais pas layout
$router->get('/liste', [PageController::class, 'show', ['Liste', true]]); // utilise websocket

$router->get('/redeem', [PageController::class, 'show', ['Coupons']], [
    'middleware' => 'auth'
]);



$router->post('/api/queue/add', [QueueController::class, 'add'], [
    'middleware' => 'auth',
    'middleware' => 'permission:addToWaitingList'
]);



$router->get('/api/coupons/list', [CouponController::class, 'list'], [
    'middleware' => 'auth',
]);

$router->post('/api/coupons/create', [CouponController::class, 'create'], [
    'middleware' => 'auth',
    'middleware' => 'permission:createCoupons'
]);

$router->post('/api/coupons/delete', [CouponController::class, 'delete'], [
    'middleware' => 'auth',
    // 'middleware' => 'permission:createCoupons' // on peut supprimer que ses propres coupons, pas besoin d'une permission spécifique
]);

$router->post('/api/coupons/consume', [CouponController::class, 'consume'], [
    'middleware' => 'auth',
    'middleware' => 'permission:consumeCoupons'
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