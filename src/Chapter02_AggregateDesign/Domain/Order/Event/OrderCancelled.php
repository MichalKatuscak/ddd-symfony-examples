<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order\Event;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;

final readonly class OrderCancelled
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public string $reason,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
}
