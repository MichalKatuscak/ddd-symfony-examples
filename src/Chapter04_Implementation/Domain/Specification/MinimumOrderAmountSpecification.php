<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Specification;

use App\Chapter04_Implementation\Domain\Order\Order;

final readonly class MinimumOrderAmountSpecification
{
    public function __construct(
        private int $minimumAmountCents,
    ) {}

    public function isSatisfiedBy(Order $order): bool
    {
        return $order->total()->amount >= $this->minimumAmountCents;
    }
}
