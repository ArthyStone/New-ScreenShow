<?php
declare(strict_types=1);

namespace App\Models;
use App\Core\Database;
use MongoDB\Collection;

class UserModel {
    private Collection $collection;

    public function __construct() {
        $this->collection = Database::get()->users;
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
    public function hasPermsByTwitchId(string $twitchId): ?array {
        $user = $this->collection->findOne(['twitchId' => $twitchId]);
        return $user ? $user->hasPerms : null;
    }

    public function createFromTwitch(array $data): array {
        $user = [
            "username" => $data['display_name'],
            "twitchId" => $data['id'],
            "twitchPP" => $data['profile_image_url'],
            "tickets" => 0,
            "spent_tickets" => 0,
            "perms" => ["uploadImages", "addToWaitingList"],
            "comments" => "Aucun commentaire pour le moment",
        ];

        $result = $this->collection->insertOne($user);

        $user['_id'] = $result->getInsertedId();

        return $user;
    }
    public function create(string $username, string $twitchId, string $twitchPP): void {
        $this->collection->insertOne([
            "username" => $username,
            "twitchId" => $twitchId,
            "twitchPP" => $twitchPP,
            "tickets" => 0,
            "spent_tickets" => 0,
            "perms" => ["uploadImages", "addToWaitingList"],
            "comments" => "Aucun commentaire pour le moment",
        ]);
    }
}
