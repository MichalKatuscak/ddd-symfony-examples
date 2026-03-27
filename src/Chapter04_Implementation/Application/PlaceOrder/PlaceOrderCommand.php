<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Application\PlaceOrder;

use App\Chapter04_Implementation\Domain\Order\OrderLine;

final readonly class PlaceOrderCommand
{
    /** @param OrderLine[] $lines */
    public function __construct(
        public string $customerId,
        public array $lines,
    ) {}
}
