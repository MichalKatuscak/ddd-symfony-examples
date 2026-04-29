<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Order;

use App\Shared\Domain\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public string $eventId,
        public string $orderId,
        public string $customerId,
        public int $amount,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
