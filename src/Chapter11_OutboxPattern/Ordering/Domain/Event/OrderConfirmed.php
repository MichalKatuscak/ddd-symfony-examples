<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Event;

use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;

final readonly class OrderConfirmed
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
