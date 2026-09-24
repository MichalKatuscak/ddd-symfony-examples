<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Domain\ValueObject;

final readonly class Email
{
    public function __construct(
        public string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                sprintf('Neplatný formát e-mailu: "%s".', $value),
            );
        }
    }

    public static function fromUserInput(string $raw): self
    {
        // Vstupy z formulářů normalizujeme zde (lowercase, trim).
        // Konstruktor hodnotu jen validuje a nemění – chrání invariant
        // „dvě instance se stejnou hodnotou jsou rovnocenné“.
        return new self(mb_strtolower(trim($raw)));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
