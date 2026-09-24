<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Event;

use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;

// Nese jen identitu a čas. Kdo a čím platil, ví sága z události
// platebního kontextu; agregát Order to nezajímá.
final readonly class OrderPaid
{
    public function __construct(
        public OrderId $orderId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
