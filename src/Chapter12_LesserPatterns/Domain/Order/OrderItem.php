<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

final readonly class OrderItem
{
    public function __construct(
        public string $name,
        public int $quantity,
        public Money $unitPrice,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('OrderItem quantity must be at least 1');
        }
    }

    public function lineTotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
