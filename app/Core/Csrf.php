<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    public static function generateToken(): string
    {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }

        return Session::get('csrf_token');
    }

    public static function validateToken(?string $token): bool
    {
        if (!$token) {
            return false;
        }

        $sessionToken = Session::get('csrf_token');

        return hash_equals($sessionToken ?? '', $token);
    }
}