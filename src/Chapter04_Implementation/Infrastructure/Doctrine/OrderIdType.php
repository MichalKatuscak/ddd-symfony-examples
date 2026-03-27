<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Infrastructure\Doctrine;

use App\Chapter04_Implementation\Domain\Order\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class OrderIdType extends StringType
{
    public const string NAME = 'ch04_order_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderId
    {
        if ($value === null) {
            return null;
        }
        return new OrderId((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof OrderId) {
            return $value->value;
        }
        return (string) $value;
    }
}
