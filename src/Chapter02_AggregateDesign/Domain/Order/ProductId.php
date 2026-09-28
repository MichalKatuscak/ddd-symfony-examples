<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order;

use Symfony\Component\Uid\Uuid;

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
