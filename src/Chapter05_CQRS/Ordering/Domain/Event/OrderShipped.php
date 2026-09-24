<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Event;

use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use App\Chapter05_CQRS\Shipping\Domain\ValueObject\ShipmentId;

/**
 * Kanonická událost z kapitoly Návrh agregátu. Výřez agregátu v této
 * ukázce ship() nemá; událost tu je kvůli projektoru, který ji odebírá.
 */
final readonly class OrderShipped
{
    public function __construct(
        public OrderId $orderId,
        public ShipmentId $shipmentId,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
