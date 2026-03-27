<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Events;

use App\Shared\Domain\DomainEvent;

final readonly class OrderItemAdded implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $productName,
        public readonly int $qty,
        public readonly int $lineTotalCents,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
