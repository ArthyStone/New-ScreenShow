<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Session;

class PermissionMiddleware {
    public static function handle(string $permission, string $redirectUri): void{
        if (!Session::has('user_id')) {
            header('Location: /login?redirect=' . urlencode($redirectUri));
            exit;
        }

        $requiredPermissions = array_filter(array_map('trim', explode(',', $permission)), static fn($perm) => $perm !== '');

        $permissions = Session::get('permissions');
        if (is_array($permissions)) {
            if(in_array("admin", $permissions, true)) { return; } // les admins peuvent tout bypass
            foreach ($requiredPermissions as $requiredPermission) {
                if (in_array($requiredPermission, $permissions, true)) { // si au moins une des permissions nécessaires est remplie, on autorise
                    return;
                }
            }
        }

        http_response_code(403);
        echo "Accès interdit.";
        exit;
    }
}