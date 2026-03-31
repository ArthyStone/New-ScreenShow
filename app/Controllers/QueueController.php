<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\MediaModel;
use App\Models\UserModel;

class QueueController {
    public function add(): void {
        header('Content-Type: application/json');
        $success = true;
        $input = json_decode(file_get_contents('php://input'), true);
        $duration = intval($input['duration']);
        $mediaId = $input['mediaId'];
        $priority = $input['priority'];
        $mediaModel = new MediaModel();
        $media = $mediaModel->findById($mediaId);
        $userModel = new UserModel();
        $twitchId = Session::get('user_id');
        $user = $userModel->findByTwitchId($twitchId);

        // paramètres pour le file_get_contents (requête POST au serveur de la queue)
        $data = [
            'id' => $mediaId,
            'name' => $media['name'],
            'duration' => $duration*1000,
            'type' => $media['type'],
            'username' => $user['username'],
            'user_pfp' => $user['twitchPFP'],
            'priority' => $priority
        ];
        $options = [
            'http' => [
                'header'  => "Content-type: application/json",
                'method'  => 'POST',
                'content' => json_encode($data),
            ]
        ];
        $context = stream_context_create($options);
        // consumeTickets fait de lui-même la vérification solde tickets > duration
        // ce qui fait que s'il n'y a pas le solde nécessaire, ça renvoie null et throw donc un missingTickets
        $newTicketsCount = $userModel->consumeTicketsByTwitchId($twitchId, $priority ? $duration * 3 : $duration);
        if($newTicketsCount !== null) {
            Session::set('user_tickets', (string) $newTicketsCount ?? 0);
        } else {
            http_response_code(422);
            echo json_encode(["success" => false, 'reason' => 'missingTickets']);
            exit;
        }
        // cette ligne ne peut donc s'exécuter que s'il y a assez de tickets dans le solde de l'utilisateur
        $response = file_get_contents($_ENV['QUEUE_SERVER_POST'].'/add', false, $context);
        // ensuite, si il y a eu un problème avec l'ajout à la file d'attente (sûrement que le serveur a crash), on rend les tickets
        // Si ça arrive, on rembourse mais surtout en met success à false pour notifier l'utilisateur dans le front
        if(!json_decode($response)->success) {
            $newTicketsCount = $userModel->refundTicketsByTwitchId($twitchId, (int)$duration);
            $success = false;
        };
        http_response_code(200);
        echo json_encode(["success" => $success, 'newTicketsCount' => $newTicketsCount]);
    }
}