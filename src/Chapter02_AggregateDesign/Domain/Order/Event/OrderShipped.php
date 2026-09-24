<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order\Event;

use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter02_AggregateDesign\Domain\Shipping\ShipmentId;

final readonly class OrderShipped
{
    public function __construct(
        public OrderId $orderId,
        public ShipmentId $shipmentId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
