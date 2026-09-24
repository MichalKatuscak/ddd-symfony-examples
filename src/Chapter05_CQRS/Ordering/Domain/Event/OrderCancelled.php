<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Event;

use App\Chapter05_CQRS\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;

/**
 * Kanonická událost z kapitoly Návrh agregátu. Výřez agregátu v této
 * ukázce cancel() nemá; událost tu je kvůli projektoru, který ji odebírá.
 */
final readonly class OrderCancelled
{
    public function __construct(
        public OrderId $orderId,
        public CustomerId $customerId,
        public string $reason,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
