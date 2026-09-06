<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

use Symfony\Component\Uid\Uuid;

/**
 * Vlastníka objednávky drží agregát jako identitu, ne jako řetězec.
 * Konvence knihy: agregáty se odkazují přes ID, a to je hodnotový objekt.
 */
final readonly class CustomerId
{
    public function __construct(public string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('CustomerId cannot be empty');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
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
