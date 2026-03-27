<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Events;

use App\Shared\Domain\DomainEvent;

final readonly class OrderItemAdded implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public string $productName,
        public int $qty,
        public int $lineTotalCents,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
