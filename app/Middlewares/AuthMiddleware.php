<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Session;

class AuthMiddleware {
    public static function handle(string $redirectUri): void {
        if (!Session::has('user_id')) {
            header('Location: /login?redirect=' . urlencode($redirectUri));
            exit;
        }
    }
}