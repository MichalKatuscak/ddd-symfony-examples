<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

final class OrderItem
{
    public function __construct(
        private readonly string $name,
        private readonly int $quantity,
        private readonly Money $unitPrice,
    ) {}

    public function name(): string { return $this->name; }
    public function quantity(): int { return $this->quantity; }
    public function unitPrice(): Money { return $this->unitPrice; }
    public function lineTotal(): Money { return $this->unitPrice->multiply($this->quantity); }
}
