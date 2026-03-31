<?php
declare(strict_types=1);

namespace App\Models;
use App\Core\Database;
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Operation\FindOneAndUpdate;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use DateTime;

class CouponModel {
    private Collection $collection;

    public function __construct() {
        $this->collection = Database::get()->Coupons;
    }

    private static function getRandomHex($num_bytes=5): string {
        return bin2hex(openssl_random_pseudo_bytes($num_bytes));
    }

    public function create(string $twitchId, int $value, int $uses, string $code = null, int $daysValid = 14): ?array {
        $now = new DateTime();
        $expiresAt = (clone $now)->modify("+{$daysValid} days");

        $couponCode = $code ?? self::getRandomHex();

        $document = [
            'code'          => $couponCode,
            'createdBy'     => $twitchId,
            'createdAt'     => new UTCDateTime($now->getTimestamp() * 1000),
            'expiresAt'     => new UTCDateTime($expiresAt->getTimestamp() * 1000),
            'remainingUses' => $uses,
            'usedBy'        => [],
            'value'         => $value,
            'uses'          => $uses,
        ];

        try {
            $result = $this->collection->insertOne($document);

            if ($result->getInsertedCount() === 0) return null;

            $document['_id'] = $result->getInsertedId();
            return $document;

        } catch (BulkWriteException $err) {
            if ($err->getCode() === 11000 && $code === null) {
                return $this->create($twitchId, $value, $uses, null, $daysValid);
            }
            // Erreur 11000 avec code manuel = duplicata
            if ($err->getCode() === 11000) {
                return ['error' => 'duplicateCode'];
            }
            throw $err;
        }
    }

    /**
     * Retourne ['coupon' => array] en cas de succès,
     * ou ['error' => string] en cas d'échec.
     */
    public function consume(string $twitchId, string $code): array {
        $now = new UTCDateTime(time() * 1000);

        $result = $this->collection->findOneAndUpdate(
            [
                'code'          => $code,
                'createdBy'     => ['$ne' => $twitchId],
                'usedBy'        => ['$ne' => $twitchId],
                'remainingUses' => ['$gt' => 0],
                'expiresAt'     => ['$gt' => $now],
            ],
            [
                '$inc'  => ['remainingUses' => -1],
                '$push' => ['usedBy' => $twitchId],
            ],
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );

        if ($result) {
            return ['coupon' => $result->getArrayCopy()];
        }

        // Diagnostic : on cherche le coupon sans filtres pour savoir pourquoi
        $coupon = $this->collection->findOne(['code' => $code]);

        if (!$coupon) {
            return ['error' => 'notFound'];
        }

        $coupon = $coupon->getArrayCopy();

        if ($coupon['createdBy'] === $twitchId) {
            return ['error' => 'ownCoupon'];
        }

        if (in_array($twitchId, (array) $coupon['usedBy'], true)) {
            return ['error' => 'alreadyUsed'];
        }

        if ($coupon['remainingUses'] <= 0) {
            return ['error' => 'exhausted'];
        }

        if ($coupon['expiresAt'] <= $now) {
            return ['error' => 'expired'];
        }

        return ['error' => 'unknown'];
    }

    public function delete(string $twitchId, string $code): bool {
        $result = $this->collection->deleteOne([
            'code'      => $code,
            'createdBy' => $twitchId,
        ]);

        return $result->getDeletedCount() > 0;
    }

    public function findByCode(string $code): ?array {
        $coupon = $this->collection->findOne(['code' => $code]);
        return $coupon ? $coupon->getArrayCopy() : null;
    }

    public function findAllByTwitchId(string $twitchId): array {
        $cursor = $this->collection->find(
            ['createdBy' => $twitchId],
            ['sort' => ['createdAt' => -1]]
        );
        return array_map(fn($doc) => $doc->getArrayCopy(), $cursor->toArray());
    }
}