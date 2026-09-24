<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Infrastructure\Doctrine\Type;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

/**
 * Registrovaný pod jménem ch04_user_id (kniha: user_id). Převod selže
 * hlasitě – kdyby na neočekávaný vstup vracel null, dotaz by tiše nic
 * nenašel.
 */
final class UserIdType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?UserId
    {
        return $value === null ? null : UserId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null || $value instanceof UserId) {
            return $value?->value;
        }

        throw InvalidType::new($value, self::class, ['null', UserId::class]);
    }

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 36, 'fixed' => true]);
    }
}
