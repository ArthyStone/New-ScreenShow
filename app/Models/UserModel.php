<?php
declare(strict_types=1);

namespace App\Models;
use App\Core\Database;
use MongoDB\Collection;

class UserModel {
    private Collection $collection;

    public function __construct() {
        $this->collection = Database::get()->users; // lorsqu'on passera sur Users, il faudra aussi changer le nom de la collection dans MediaModel sur l'aggregat
    }



    private array $user = [];
    public function setUser(array $user): void {
        $this->user = $user;
    }
    public function hasPermission(string $permission): bool {
        if (!isset($this->user['permissions'])) {
            return false;
        }

        return in_array($permission, $this->user['permissions']);
    }



    public function findByTwitchId(string $twitchId): ?array {
        $user = $this->collection->findOne(['twitchId' => $twitchId]);
        return $user ? $user->getArrayCopy() : null;
    }
    public function findByUsername(string $username): ?array {
        $user = $this->collection->findOne(['username' => $username]);
        return $user ? $user->getArrayCopy() : null;
    }
    public function hasPermissionByTwitchId(string $twitchId, string $permission): ?bool {
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
}
