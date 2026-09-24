<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Event;

use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Chapter11_OutboxPattern\Shipping\Domain\ValueObject\ShipmentId;

final readonly class OrderShipped
{
    public function __construct(
        public OrderId $orderId,
        public ShipmentId $shipmentId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
