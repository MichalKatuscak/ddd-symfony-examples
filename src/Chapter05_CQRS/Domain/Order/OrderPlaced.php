<?php

declare(strict_types=1);
namespace App\Chapter05_CQRS\Domain\Order;
use App\Shared\Domain\DomainEvent;
final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public string $orderId,
        public string $customerId,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
