<?php
declare(strict_types=1);

namespace App\Core;
use MongoDB\Client;
use MongoDB\Database as MongoDatabase;

class Database {
    private static ?MongoDatabase $db = null;

    public static function get(): MongoDatabase {
        if (self::$db === null) {
            $client = new Client($_ENV['MONGO_DSN']);
            self::$db = $client->selectDatabase($_ENV['MONGO_DB']);
        }

        return self::$db;
    }
}
