<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

final readonly class OrderItem
{
    public function __construct(
        private string $name,
        private int $quantity,
        private Money $unitPrice,
    ) {}

    public function name(): string { return $this->name; }
    public function quantity(): int { return $this->quantity; }
    public function unitPrice(): Money { return $this->unitPrice; }
    public function lineTotal(): Money { return $this->unitPrice->multiply($this->quantity); }
}
