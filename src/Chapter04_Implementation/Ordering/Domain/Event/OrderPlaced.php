<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Ordering\Domain\Event;

use App\Chapter04_Implementation\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderId;

// Kanonický tvar z kapitoly Základní koncepty: hodnotové objekty a čas vzniku.
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
