<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Application\PlaceOrder;

final readonly class PlaceOrderCommand
{
    /** @param list<array{name: string, quantity: int, unitPriceMinorUnits: int}> $items */
    public function __construct(
        public string $customerId,
        public string $shippingCountry,
        public array $items,
    ) {}
}
