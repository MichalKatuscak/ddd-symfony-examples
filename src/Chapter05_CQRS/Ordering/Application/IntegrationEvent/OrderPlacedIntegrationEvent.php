<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Application\IntegrationEvent;

use Symfony\Component\Uid\Uuid;

/**
 * Integrační událost z kapitoly Outbox Pattern – neměnná, serializovatelná,
 * nese jen primitivy. Na ní stojí projektor dashboardu.
 */
final readonly class OrderPlacedIntegrationEvent
{
    /**
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
     */
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $customerId,
        public array $items,
        public int $totalAmountCents,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
