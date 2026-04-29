<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Order;

use App\Shared\Domain\DomainEvent;

final readonly class OrderCancelled implements DomainEvent
{
    public function __construct(
        public string $eventId,
        public string $orderId,
        public string $reason,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
