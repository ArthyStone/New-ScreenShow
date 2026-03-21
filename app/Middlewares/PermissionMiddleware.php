<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Session;
use App\Models\UserModel;

class PermissionMiddleware {
    public static function handle(string $permission, string $redirectUri): void{
        if (!Session::has('user_id')) {
            header('Location: /login?redirect=' . urlencode($redirectUri));
            exit;
        }
        $twitchId = Session::get('user_id');
        $userModel = new UserModel();
        if (!$userModel->hasPermissionByTwitchId($twitchId, $permission)) {
            http_response_code(403);
            echo "Accès interdit.";
            exit;
        }
    }
}