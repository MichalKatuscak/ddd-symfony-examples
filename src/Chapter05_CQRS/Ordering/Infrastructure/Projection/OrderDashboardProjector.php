<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Infrastructure\Projection;

use App\Chapter05_CQRS\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderCancelled;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderShipped;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Projektor: naslouchá událostem a aktualizuje denormalizovanou tabulku
 * pro obrazovku „Přehled objednávek“ (kniha: order_dashboard, zde
 * ch05_order_dashboard, protože ukázky sdílejí jednu databázi).
 *
 * Kanonický routing ho v knize nechává na synchronním event.bus, takže
 * řádek vzniká ve stejné transakci jako zápis agregátu. Integrační
 * OrderPlacedIntegrationEvent tam přichází z outboxu přes relay; ukázka
 * outbox nemá a pošle ji rovnou PlaceOrderHandler.
 */
// Priorita je nutná: na synchronní sběrnici běží posluchači v pořadí
// registrace a Process Manager z kapitoly o ságách odebírá tutéž událost.
// V této ukázce jiný posluchač není; atribut zůstává, aby projektor seděl
// s knihou.
#[AsMessageHandler(bus: 'event.bus', priority: 10)]
final class OrderDashboardProjector
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function __invoke(
        OrderPlacedIntegrationEvent|OrderShipped|OrderCancelled $event,
    ): void {
        match (true) {
            $event instanceof OrderPlacedIntegrationEvent => $this->onOrderPlaced($event),
            $event instanceof OrderShipped => $this->onOrderShipped($event),
            $event instanceof OrderCancelled => $this->onOrderCancelled($event),
        };
    }

    private function onOrderPlaced(OrderPlacedIntegrationEvent $event): void
    {
        $this->connection->executeStatement(
            'INSERT INTO ch05_order_dashboard
                (order_id, customer_id, total_amount, status, placed_at, updated_at)
             VALUES (:orderId, :customerId, :totalAmount, :status, :placedAt, :updatedAt)
             -- ON CONFLICT je PostgreSQL i SQLite; MySQL má
             -- ON DUPLICATE KEY UPDATE … = VALUES(…). Upsert není přenositelný.
             -- Bez podmínky by opakované doručení vrátilo odeslanou
             -- objednávku zpět na „placed“. Řádek se přepíše jen tehdy,
             -- když je nová událost novější než ta zapsaná.
             ON CONFLICT (order_id) DO UPDATE SET
                status = excluded.status, updated_at = excluded.updated_at
             WHERE ch05_order_dashboard.updated_at < excluded.updated_at',
            [
                'orderId'     => $event->orderId,
                'customerId'  => $event->customerId,
                'totalAmount' => $event->totalAmountCents,
                // Slovník obrazovky, ne hodnota enumu OrderStatus: agregát
                // je po placeWithItems() ve stavu Confirmed.
                'status'      => 'placed',
                'placedAt'    => $event->occurredAt->format('Y-m-d H:i:s.u'),
                'updatedAt'   => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ],
        );
    }

    private function onOrderShipped(OrderShipped $event): void
    {
        $this->connection->executeStatement(
            'UPDATE ch05_order_dashboard
                SET status = :status,
                    shipment_id = :shipmentId,
                    updated_at = :updatedAt
              WHERE order_id = :orderId
                AND updated_at < :updatedAt',
            [
                // Událost nese OrderId, DBAL do dotazu potřebuje skalár.
                'orderId'    => $event->orderId->value,
                'status'     => 'shipped',
                'shipmentId' => $event->shipmentId->value,
                'updatedAt'  => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ],
        );
    }

    private function onOrderCancelled(OrderCancelled $event): void
    {
        $this->connection->executeStatement(
            'UPDATE ch05_order_dashboard
                SET status = :status, updated_at = :updatedAt
              WHERE order_id = :orderId
                AND updated_at < :updatedAt',
            [
                'orderId'   => $event->orderId->value,
                'status'    => 'cancelled',
                'updatedAt' => $event->occurredAt->format('Y-m-d H:i:s.u'),
            ],
        );
    }
}
