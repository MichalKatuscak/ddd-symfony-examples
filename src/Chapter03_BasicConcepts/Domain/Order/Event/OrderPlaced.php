<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Event;

use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;

// Tenká událost: identifikátory a čas. Příjemce uvnitř kontextu má
// k agregátu přístup a zbytek si dotáhne sám.
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
