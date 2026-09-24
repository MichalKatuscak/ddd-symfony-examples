<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order\Event;

use App\Chapter02_AggregateDesign\Domain\Order\OrderId;

// Nese jen identitu a čas. Kdo a čím platil, ví sága z události
// platebního kontextu; agregát Order to nezajímá.
final readonly class OrderPaid
{
    public function __construct(
        public OrderId $orderId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
