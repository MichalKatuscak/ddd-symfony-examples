<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Event;

use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;

/**
 * Doménová událost: nese hodnotové objekty a zůstává uvnitř kontextu
 * Ordering. Přes hranici jde OrderPlacedIntegrationEvent.
 */
final readonly class OrderPlaced
{
    public \DateTimeImmutable $occurredAt;

    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
