<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Infrastructure\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;

final class MoneyAmountType extends IntegerType
{
    public const string NAME = 'ch04_money_amount';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): int
    {
        return (int) $value;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int
    {
        return (int) $value;
    }
}
