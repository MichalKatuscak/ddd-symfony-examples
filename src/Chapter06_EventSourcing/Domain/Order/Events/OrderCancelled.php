<?php

declare(strict_types=1);
namespace App\Chapter06_EventSourcing\Domain\Order\Events;
use App\Shared\Domain\DomainEvent;
final readonly class OrderCancelled implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $reason,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
