<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Session;
use App\Models\MediaModel;

class QueueController {
    public function add(): void {
        $mediaModel = new MediaModel();
        $data = [
            'user_name' => Session::get('user_name'),
            'user_pfp' => Session::get('user_pfp'),
            'user_tickets' => Session::get('user_tickets'),
            'medias' => $mediaModel->findAllAggregate(),
            'tags' => json_decode(str_replace('_', ' ', $_ENV['TAGS']))
        ];
        View::render("images", $data);
    }
}