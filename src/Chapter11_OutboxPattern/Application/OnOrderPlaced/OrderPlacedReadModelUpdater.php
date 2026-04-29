<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Application\OnOrderPlaced;

use App\Chapter11_OutboxPattern\Domain\Inbox\InboxRepositoryInterface;
use App\Chapter11_OutboxPattern\Domain\Inbox\ProcessedEventId;
use App\Chapter11_OutboxPattern\Domain\Order\OrderPlaced;
use App\Chapter11_OutboxPattern\Infrastructure\InMemoryReadModelStore;

/**
 * Idempotentní subscriber — aktualizuje read model po události OrderPlaced.
 *
 * Outbox relay garantuje at-least-once delivery — stejnou událost můžeme
 * dostat několikrát (po retry, po crashi mezi ack a side-effectem, atd.).
 * Inbox table drží IDs zpracovaných eventů; pokud event už zpracovaný byl,
 * tento handler ho jen "ack-ne" a nedělá žádné další postranní efekty.
 *
 * V produkci by check-and-mark proběhl v jedné transakci spolu s update
 * read modelu — UNIQUE constraint na (consumer, event_id) zabrání duplicitě
 * i při souběžném zpracování.
 */
final readonly class OrderPlacedReadModelUpdater
{
    public const string CONSUMER_NAME = 'orders.read_model';

    public function __construct(
        private InboxRepositoryInterface $inbox,
        private InMemoryReadModelStore $readModel,
    ) {
    }

    public function __invoke(OrderPlaced $event): void
    {
        if ($this->inbox->isProcessed(self::CONSUMER_NAME, $event->eventId)) {
            // Duplicate — ack & skip. Pokud bychom tento řádek vynechali,
            // počítadlo updatesApplied by stoupalo s každým doručením.
            return;
        }

        $this->readModel->recordOrderPlaced(
            orderId: $event->orderId,
            customerId: $event->customerId,
            amount: $event->amount,
            placedAt: $event->occurredAt(),
        );

        $this->inbox->markProcessed(new ProcessedEventId(
            consumerName: self::CONSUMER_NAME,
            eventId: $event->eventId,
        ));
    }
}
