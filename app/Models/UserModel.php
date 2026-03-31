<?php
declare(strict_types=1);

namespace App\Models;
use App\Core\Database;
use MongoDB\Collection;
use MongoDB\Operation\FindOneAndUpdate;

class UserModel {
    private Collection $collection;

    public function __construct() {
        $this->collection = Database::get()->users; // lorsqu'on passera sur Users, il faudra aussi changer le nom de la collection dans MediaModel sur l'aggregat
    }

    public function findByTwitchId(string $twitchId): ?array {
        $user = $this->collection->findOne(['twitchId' => $twitchId]);
        return $user ? $user->getArrayCopy() : null;
    }
    public function findByUsername(string $username): ?array {
        $user = $this->collection->findOne(['username' => $username]);
        return $user ? $user->getArrayCopy() : null;
    }
    public function hasPermissionByTwitchId(string $twitchId, string $permission): bool {
        $user = $this->findByTwitchId($twitchId);
        $perms = $user['perms']->getArrayCopy() ?? null; // de base, on reçoit un BSON array, on le convertit en array PHP
        if ($user && isset($user['perms']) && in_array($permission, $perms)) {
            return true;
        }
        return false;
    }

    public function createFromTwitch(array $data): array {
        $user = [
            "username" => $data['display_name'],
            "twitchId" => $data['id'],
            "twitchPFP" => $data['profile_image_url'],
            "comments" => "Aucun commentaire pour le moment",
            "tickets" => 0,
            "spent_tickets" => 0,
            "perms" => ["uploadImages", "addToWaitingList"],
        ];

        $result = $this->collection->insertOne($user);

        $user['_id'] = $result->getInsertedId();

        return $user;
    }

    public function incTicketsByTwitchId(string $twitchId, int $tickets): ?int {
        $filter = ['twitchId' => $twitchId];
        if ($tickets < 0) {
            $filter['tickets'] = ['$gte' => -$tickets];
        }

        $result = $this->collection->findOneAndUpdate(
            $filter,
            ['$inc' => ['tickets' => $tickets]],
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );
        if (!$result) {
            if ($tickets < 0) {
                throw new \RuntimeException("Solde de tickets insuffisant.");
            }
            return null; // utilisateur introuvable
        }

        return $result->getArrayCopy()['tickets'];
    }

    public function consumeTicketsByTwitchId(string $twitchId, int $tickets): ?int {
        $result = $this->collection->findOneAndUpdate(
            ['twitchId' => $twitchId, 'tickets' => ['$gte' => $tickets]],
            ['$inc' => ['tickets' => -$tickets, 'spent_tickets' => $tickets]],
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );
        return $result ? $result->getArrayCopy()['tickets'] : null;
    }

    public function refundTicketsByTwitchId(string $twitchId, int $tickets): ?int {
        $result = $this->collection->findOneAndUpdate(
            ['twitchId' => $twitchId],
            ['$inc' => ['tickets' => $tickets, 'spent_tickets' => -$tickets]],
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );
        return $result ? $result->getArrayCopy()['tickets'] : null;
    }
}
