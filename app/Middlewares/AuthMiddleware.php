<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Session;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }
    }
}