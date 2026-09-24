<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\Ordering\Projection;

use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderConfirmed;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderItemAdded;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderPlaced;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderShipped;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Projektor budující tabulku order summary z doménových událostí.
 *
 * Každá metoda handle*() odpovídá jednomu typu události a je registrována
 * jako samostatný Messenger handler atributem na úrovni metody.
 *
 * Kniha události routuje na asynchronní transport. V ukázce transport
 * chybí, takže event.bus projektor volá synchronně hned po zápisu do
 * Event Store – pragmatický kompromis ze sekce 13.09.
 */
final class OrderSummaryProjector
{
    public const TABLE = 'ch06_order_summary';

    public function __construct(
        private readonly Connection $connection,
    ) {}

    #[AsMessageHandler(bus: 'event.bus')]
    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $this->connection->insert(self::TABLE, [
            'order_id'      => $event->orderId,
            'customer_id'   => $event->customerId,
            'status'        => 'draft',
            'item_count'    => 0,
            'total_amount'  => 0,
            'placed_at'     => $event->occurredAt->format('Y-m-d H:i:s'),
            'shipped_at'    => null,
            'tracking_no'   => null,
        ]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function handleOrderItemAdded(OrderItemAdded $event): void
    {
        // item_count počítá řádky objednávky, total_amount haléře.
        // Cena řádku je množství krát jednotková cena.
        $lineTotal = $event->item->quantity * $event->item->unitPriceInCents;

        $this->connection->executeStatement(
            'UPDATE ' . self::TABLE . '
                SET item_count   = item_count + 1,
                    total_amount = total_amount + :lineTotal
              WHERE order_id = :orderId',
            ['lineTotal' => $lineTotal, 'orderId' => $event->orderId],
        );
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function handleOrderConfirmed(OrderConfirmed $event): void
    {
        $this->connection->executeStatement(
            'UPDATE ' . self::TABLE . ' SET status = :status WHERE order_id = :orderId',
            ['status' => 'confirmed', 'orderId' => $event->orderId],
        );
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function handleOrderShipped(OrderShipped $event): void
    {
        $this->connection->executeStatement(
            'UPDATE ' . self::TABLE . '
                SET status      = :status,
                    shipped_at  = :shippedAt,
                    tracking_no = :trackingNo
              WHERE order_id = :orderId',
            [
                'status'     => 'shipped',
                'shippedAt'  => $event->occurredAt->format('Y-m-d H:i:s'),
                'trackingNo' => $event->trackingNumber,
                'orderId'    => $event->orderId,
            ],
        );
    }
}
