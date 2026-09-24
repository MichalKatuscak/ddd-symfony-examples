<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Reporting\Application\Subscriber;

use App\Chapter11_OutboxPattern\Inbox\Application\InboxRepository;
use App\Chapter11_OutboxPattern\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter11_OutboxPattern\Reporting\Application\ReadModelStore;

/**
 * Idempotentní subscriber: inbox check, vedlejší efekt a záznam do inboxu.
 *
 * V knize nese #[AsMessageHandler(bus: 'event.bus', priority: 10)] a tělo
 * obaluje $em->wrapInTransaction(), takže kontrola, upsert i zápis do inboxu
 * se commitnou spolu. Ukázka ho volá přímo z InProcessPublisher a pracuje
 * nad úložišti v paměti, kde transakce není. Že duplicita z UNIQUE constraintu
 * shodí celou transakci i s efektem, ověřuje DbalInboxRepositoryTest.
 */
final readonly class OrderPlacedReadModelUpdater
{
    public const string CONSUMER = 'reporting.order_placed';

    public function __construct(
        private InboxRepository $inbox,
        private ReadModelStore $readModel,
    ) {}

    public function __invoke(OrderPlacedIntegrationEvent $event): void
    {
        // 1) Kontrola idempotence – duplikát se ackne bez vedlejšího efektu.
        if ($this->inbox->isProcessed($event->eventId, self::CONSUMER)) {
            return;
        }

        // 2) Vlastní logika subscribera – upsert read modelu.
        $this->readModel->upsertOrderRow(
            orderId: $event->orderId,
            customerId: $event->customerId,
            items: $event->items,
            placedAt: $event->occurredAt,
        );

        // 3) Záznam do inboxu. UNIQUE constraint je pojistka proti souběhu:
        // druhý worker dostane UniqueConstraintViolationException, transakce
        // se vrátí a Messenger zprávu zopakuje – podruhé ji zastaví krok 1.
        $this->inbox->markProcessed($event->eventId, self::CONSUMER);
    }
}
