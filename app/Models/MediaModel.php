<?php
declare(strict_types=1);

namespace App\Models;
use App\Core\Database;
use MongoDB\Collection;
use MONGODB\BSON\ObjectId;

class MediaModel {
    private Collection $collection;

    public function __construct() {
        $this->collection = Database::get()->Uploads;
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
    public function findAll(): array {
        $media = $this->collection->find();
        return iterator_to_array($media);
    }
    public function findByState(string $state): array {
        $media = $this->collection->find(['state' => $state]);
        return iterator_to_array($media);
    }
    public function findByTag(string $tag): array {
        $media = $this->collection->find(['tags' => ['$in' => [$tag]]]);
        return iterator_to_array($media);
    }
    public function findByType(string $type): array {
        $media = $this->collection->find(['type' => $type]);
        return iterator_to_array($media);
    }
    public function findByName(string $name): array {
        $media = $this->collection->find(['name' => ['$regex' => $name, '$options' => 'i']]);
        return iterator_to_array($media);
    }
    public function findByAuthorTwitchId(string $twitchId): array {
        $media = $this->collection->find(['created_by.twitchId' => $twitchId]);
        return iterator_to_array($media);
    }

    public function findByMultipleParameters(?string $state, ?string $tag, ?string $type, ?string $name): array {
        $query = [];
        if ($state) {
            $query['state'] = $state;
        }
        if ($tag) {
            $query['tags'] = ['$in' => [$tag]];
        }
        if ($type) {
            $query['type'] = $type;
        }
        if ($name) {
            $query['name'] = ['$regex' => $name, '$options' => 'i'];
        }
        $media = $this->collection->find($query);
        return iterator_to_array($media);
    }
    public function findAllAggregate() {
        $media = $this->collection->aggregate([
            [
                '$lookup' => [
                    'from' => 'Users', // au final, j'ai déjà mis Users, mais il faudra penser à changer twitchPP en twitchPFP dans la collection Users
                    'localField' => 'created_by.twitchId',
                    'foreignField' => 'twitchId',
                    'as' => 'creator_info'
                ]
            ],
            [
                '$unwind' => '$creator_info'
            ],
            [
                '$addFields' => [
                    'created_by.twitchPFP' => '$creator_info.twitchPP',
                ]
            ],
            [
                '$project' => [
                    '_id' => 1,
                    'type' => 1,
                    'name' => 1,
                    'tags' => 1,
                    'state' => 1,
                    'spent_tickets' => 1,
                    'created_by' => [
                        'twitchId'   => '$created_by.twitchId',
                        'username'   => '$creator_info.username',
                        'twitchPFP'  => '$creator_info.twitchPP'
                    ]
                ]
            ]
        ]);
        return iterator_to_array($media);
    }


    public function setState(string $_id, string $state): bool {
        $result = $this->collection->updateOne(
            ['_id' => $_id],
            ['$set' => ['state' => $state]]
        );
        return $result->getModifiedCount() > 0;
    }

    public function getState(string $_id): ?string {
        $media = $this->collection->findOne(['_id' => $_id]);
        if ($media && isset($media['state'])) {
            return $media['state'];
        }
        return "UNKNOWN";
    }



    public function isApproved(string $_id): ?bool {
        $media = $this->collection->findOne(['_id' => $_id]);
        if ($media && isset($media['state']) && $media['state'] === 'APPROVED') {
            return true;
        }
        return false;
    }
    public function isPending(string $_id): ?bool {
        $media = $this->collection->findOne(['_id' => $_id]);
        if ($media && isset($media['state']) && $media['state'] === 'PENDING') {
            return true;
        }
        return false;
    }
    public function isRefused(string $_id): ?bool {
        $media = $this->collection->findOne(['_id' => $_id]);
        if ($media && isset($media['state']) && $media['state'] === 'REFUSED') {
            return true;
        }
        return false;
    }



    public function create(string $type, string $name, array $tags, array $author): array {
        $media = [
            "type" => $type,
            "name" => $name,
            "tags" => $tags,
            "state" => "PENDING",
            "used" => 0,
            "created_by" => [
                "username" => $author['username'],
                "twitchId" => $author['twitchId']
            ],
            "spent_tickets" => 0
        ];
        $result = $this->collection->insertOne($media);
        $media['_id'] = $result->getInsertedId();
        return $media;
    }
    public function delete(string $_id): bool {
        $result = $this->collection->deleteOne(['_id' => $_id]);
        return $result->getDeletedCount() > 0;
    }
    public function planDelete(string $_id): bool {
        $date = new DateTime('now', new DateTimeZone('Europe/Paris'));
        $date->modify('+15 days');
        $date->setTime(0, 0, 0);
        $timestamp = $date->getTimestamp();
        $result = $this->collection->updateOne(
            ['_id' => $_id],
            ['$set' => [
                'state' => 'REFUSED',
                'deletionTimestamp' => $timestamp
            ]]
        );
        return $result->getModifiedCount() > 0;
    }
    public function update(string $_id, array $data): bool {
        $updateData = [];
        if (isset($data['type'])) {
            $updateData['type'] = $data['type'];
        }
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['tags'])) {
            $updateData['tags'] = $data['tags'];
        }
        if (isset($data['state'])) {
            $updateData['state'] = $data['state'];
        }
        if (isset($data['used'])) {
            $updateData['used'] = $data['used'];
        }
        if (isset($data['spent_tickets'])) {
            $updateData['spent_tickets'] = $data['spent_tickets'];
        }
        if (empty($updateData)) {
            return false; // Rien à mettre à jour
        }
        $result = $this->collection->updateOne(
            ['_id' => $_id],
            ['$set' => $updateData]
        );
        return $result->getModifiedCount() > 0;
    }
    public function incrementUsedAndAddSpentTickets(string $_id, int $spentTickets): bool {
        $result = $this->collection->updateOne(
            ['_id' => $_id],
            ['$inc' => ['used' => 1, 'spent_tickets' => $spentTickets]]
        );
        return $result->getModifiedCount() > 0;
    }
}
