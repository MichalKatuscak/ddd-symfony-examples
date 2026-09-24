<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\User;

use Symfony\Component\Uid\Uuid;

final readonly class UserId
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('UserId must be a valid UUID');
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

    // Doctrine převádí identitu na řetězec při každém persist().
    // Bez __toString() spadne už uložení – viz kapitola o implementaci.
    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
