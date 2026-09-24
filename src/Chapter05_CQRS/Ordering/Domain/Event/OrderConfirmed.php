<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Event;

use App\Chapter05_CQRS\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;

final readonly class OrderConfirmed
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
