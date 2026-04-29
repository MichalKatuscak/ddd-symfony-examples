<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Application\PlaceOrder;

final readonly class PlaceOrderCommand
{
    public function __construct(
        public string $customerId,
        public int $amount,
    ) {
    }
}
