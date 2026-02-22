<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Session;
use App\Models\UserModel;

class PermissionMiddleware {
    public static function handle(string $permission): void{
        if (!Session::has('user')) {
            header('Location: /login');
            exit;
        }

        $userData = Session::get('user');

        $userModel = new UserModel();
        $userModel->setUser($userData);

        if (!$userModel->hasPermission($permission)) {
            http_response_code(403);
            echo "Accès interdit.";
            exit;
        }
    }
}