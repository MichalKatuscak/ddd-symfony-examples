<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Infrastructure\Doctrine\Type;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Registrovaný pod jménem ch04_email (kniha: email_vo), viz
 * config/packages/chapter04_implementation.yaml.
 */
final class EmailType extends StringType
{
    public const NAME = 'ch04_email';

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Email
    {
        if ($value === null) {
            return null;
        }

        return new Email((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof Email ? $value->value : (string) $value;
    }

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 255]);
    }
}
