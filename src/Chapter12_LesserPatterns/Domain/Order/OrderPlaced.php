<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Shared\Domain\DomainEvent;

final readonly class OrderPlaced implements DomainEvent
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public Money $total,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
