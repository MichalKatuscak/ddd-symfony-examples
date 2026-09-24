<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class UserId
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(
                sprintf('Neplatné UserId: "%s".', $value),
            );
        }
    }

    public static function generate(): self
    {
        return new self((string) Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    // Doctrine skládá klíč identity mapy přes implode() nad identifikátorem.
    // Bez __toString() padne už persist().
    public function __toString(): string
    {
        return $this->value;
    }
}
