<?php
namespace App\Chapter05_CQRS\Domain\Order;
use App\Shared\Domain\DomainEvent;
final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
