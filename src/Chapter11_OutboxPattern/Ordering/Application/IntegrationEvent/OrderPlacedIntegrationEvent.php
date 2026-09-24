<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent;

use Symfony\Component\Uid\Uuid;

/**
 * Integrační událost – neměnná, serializovatelná, nese pouze
 * data nutná pro subscribery. Včetně vlastního eventId pro
 * deduplikaci v Inboxu.
 *
 * Nejde o tutéž třídu jako doménová OrderPlaced: ta nese hodnotové
 * objekty a zůstává uvnitř kontextu Ordering.
 */
final readonly class OrderPlacedIntegrationEvent
{
    public function __construct(
        public Uuid $eventId,
        public string $orderId,
        public string $customerId,
        /** @var list<array{productId: string, quantity: int, unitPriceInCents: int}> */
        public array $items,
        public int $totalAmountCents,
        public \DateTimeImmutable $occurredAt,
    ) {}
}
