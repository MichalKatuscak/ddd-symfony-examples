<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Shipping;

use Symfony\Component\Uid\Uuid;

/**
 * ShipmentId patří cizímu kontextu (Shipping). Přes hranici do agregátu
 * Order jde jen identita – stejný tvar jako OrderId.
 */
final readonly class ShipmentId
{
    // Konstruktor je veřejný stejně jako u OrderId. Serializer Messengeru
    // hodnotový objekt jinak nesestaví a událost se z fronty nevrátí.
    public function __construct(
        public string $value,
    ) {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('ShipmentId must be a valid UUID');
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

    public function __toString(): string
    {
        return $this->value;
    }
}
