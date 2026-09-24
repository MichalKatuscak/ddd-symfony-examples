<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Event;

use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;

final readonly class OrderConfirmed
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
