<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

final readonly class OrderItem
{
    public function __construct(
        public ProductId $productId,
        public int $quantity,
        public Money $unitPrice,
    ) {}

    public function lineTotal(): Money { return $this->unitPrice->multiply($this->quantity); }
}
