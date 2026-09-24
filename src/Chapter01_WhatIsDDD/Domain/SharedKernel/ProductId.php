<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\SharedKernel;

use Symfony\Component\Uid\Uuid;

/**
 * Shared Kernel: identitu produktu sdílí katalog, košík i objednávky.
 * Na jejím formátu se musí shodnout všichni, kdo ji používají.
 */
final readonly class ProductId
{
    public function __construct(public string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('ProductId must be a valid UUID');
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
