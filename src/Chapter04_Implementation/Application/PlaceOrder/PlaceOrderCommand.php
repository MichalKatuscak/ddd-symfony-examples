<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Application\PlaceOrder;

final readonly class PlaceOrderCommand
{
    /** @param array<array{name: string, qty: int, price: int}> $items */
    public function __construct(
        public string $customerId,
        public array $items,
    ) {}
}
