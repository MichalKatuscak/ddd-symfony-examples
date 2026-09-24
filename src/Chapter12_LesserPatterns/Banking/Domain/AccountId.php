<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Domain;

use Symfony\Component\Uid\Uuid;

final readonly class AccountId
{
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('AccountId must be a valid UUID');
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

    public function __toString(): string
    {
        return $this->value;
    }
}
