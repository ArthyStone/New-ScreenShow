<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Core\Session;
use App\Models\MediaModel;
// use App\Models\UserModel;

class PageController {
    // Affiche une vue publique
    public function show(string $page, bool $usesWebSocket = false, bool $usesLayout = true): void {
        $viewPath = strtolower($page);
        $data = [];
        if($usesLayout) {
            $data['user_name'] = Session::get('user_name');
            $data['user_pfp'] = Session::get('user_pfp');
            $data['user_tickets'] = Session::get('user_tickets'); // on changera la valeur en session lors d'un achat
            // $userModel = new UserModel();
            // $data['user_tickets'] = $userModel->findByTwitchId(Session::get('user_id'))['tickets'];
        }
        if($usesWebSocket) {
            $data['queueServerWS'] = $_ENV['QUEUE_SERVER_WEBSOCKET'];
        }
        if($page === "Images") {
            $mediaModel = new MediaModel();
            $data['medias'] = $mediaModel->findAllAggregate();
            $data['tags'] = json_decode(str_replace('_', ' ', $_ENV['TAGS']));
        }
        View::render($viewPath, $data, $usesLayout);
    }
}