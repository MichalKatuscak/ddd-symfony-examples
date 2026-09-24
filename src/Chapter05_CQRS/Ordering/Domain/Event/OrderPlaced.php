<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Event;

use App\Chapter05_CQRS\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;

/**
 * Doménová událost: nese hodnotové objekty a zůstává uvnitř kontextu
 * Ordering. Projekci plní OrderPlacedIntegrationEvent.
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
