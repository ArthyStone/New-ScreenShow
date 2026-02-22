<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Session;

class PageController {
    // Affiche une vue publique
    public function show(string $page): void {
        $viewPath = strtolower($page);
        $data = [
            'user_name' => Session::get('user_name'),
            'user_pfp' => Session::get('user_pfp'),
            'user_tickets' => Session::get('user_tickets')
        ];
        View::render($viewPath, $data);
    }
}