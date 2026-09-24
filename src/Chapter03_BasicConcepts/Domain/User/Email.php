<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\User;

final readonly class Email
{
    public function __construct(
        public string $value,
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }
    }

    public static function fromUserInput(string $raw): self
    {
        // Normalizace vstupu (lowercase, trim) patří sem, ne do konstruktoru.
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
