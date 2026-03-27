<?php

declare(strict_types=1);
namespace App\Chapter05_CQRS\Application\PlaceOrder;
final readonly class PlaceOrderCommand
{
    /** @param array<array{name: string, qty: int, price: int}> $items */
    public function __construct(
        public readonly string $customerId,
        public readonly array $items,
    ) {}
}
