<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Application\Command;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;

final readonly class CancelOrder
{
    public function __construct(
        public OrderId $orderId,
        public string $reason,
        public CustomerId $actorId, // identita aktéra z místa vzniku
    ) {}
}
