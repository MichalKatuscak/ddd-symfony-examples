<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Cart;

use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ShippingAddress;

/**
 * Košík – vstup pro OrderFromCartFactory. Kniha ho nerozepisuje, ukázka
 * mu dává jen to, co factory čte: řádky a doručovací adresu.
 */
final class Cart
{
    /** @param list<CartLine> $lines */
    public function __construct(
        public readonly CartId $id,
        private readonly array $lines,
        private readonly ShippingAddress $shippingAddress,
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /** @return list<CartLine> */
    public function items(): array
    {
        return $this->lines;
    }

    public function shippingAddress(): ShippingAddress
    {
        return $this->shippingAddress;
    }
}
