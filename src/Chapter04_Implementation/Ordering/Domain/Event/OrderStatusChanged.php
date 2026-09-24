<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Ordering\Domain\Event;

use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderId;
use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderStatus;

final readonly class OrderStatusChanged
{
    public function __construct(
        public OrderId $orderId,
        public OrderStatus $from,
        public OrderStatus $to,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
