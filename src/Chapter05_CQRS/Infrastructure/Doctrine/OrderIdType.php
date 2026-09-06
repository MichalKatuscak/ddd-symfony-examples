<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Infrastructure\Doctrine;

use App\Chapter05_CQRS\Domain\Order\OrderId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Write model drží identitu jako hodnotový objekt; převod na sloupec
 * obstará typ, ne getter. Read model naproti tomu pracuje s primitivy —
 * o tom je celá kapitola.
 */
final class OrderIdType extends StringType
{
    public const string NAME = 'ch05_order_id';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?OrderId
    {
        return $value === null ? null : new OrderId((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof OrderId ? $value->value : (string) $value;
    }
}
