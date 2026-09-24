<?php

declare(strict_types=1);

namespace App\Tests\Chapter05\Ordering\Infrastructure;

use App\Chapter05_CQRS\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderCancelled;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderShipped;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use App\Chapter05_CQRS\Ordering\Infrastructure\Projection\OrderDashboardProjector;
use App\Chapter05_CQRS\Shipping\Domain\ValueObject\ShipmentId;
use App\Tests\Chapter05\Ordering\DashboardDatabase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrderDashboardProjectorTest extends TestCase
{
    // OrderId hodnotu nevalidního tvaru nepřijme, proto skutečná UUID.
    private const ORDER_ID       = '01a07424-28ff-7c31-9d40-6f2a1c8e5b05';
    private const OTHER_ORDER_ID = '01a07424-28ff-7c31-9d40-6f2a1c8e5b06';
    private const CUSTOMER_ID    = '01a07424-28ff-7c31-9d40-6f2a1c8e5b07';

    private Connection $connection;
    private OrderDashboardProjector $projector;

    protected function setUp(): void
    {
        $this->connection = DashboardDatabase::connection();
        $this->projector = new OrderDashboardProjector($this->connection);
    }

    private function placed(string $orderId, int $total, string $at): OrderPlacedIntegrationEvent
    {
        return new OrderPlacedIntegrationEvent(
            eventId: Uuid::v7(),
            orderId: $orderId,
            customerId: self::CUSTOMER_ID,
            items: [],
            totalAmountCents: $total,
            occurredAt: new \DateTimeImmutable($at),
        );
    }

    private function shipped(ShipmentId $shipmentId, string $at): OrderShipped
    {
        return new OrderShipped(
            orderId: OrderId::fromString(self::ORDER_ID),
            shipmentId: $shipmentId,
            occurredAt: new \DateTimeImmutable($at),
        );
    }

    /** @return array<string, mixed> */
    private function row(string $orderId): array
    {
        return $this->connection->fetchAssociative(
            'SELECT * FROM ch05_order_dashboard WHERE order_id = :id',
            ['id' => $orderId],
        ) ?: [];
    }

    public function test_projects_order_lifecycle(): void
    {
        $shipmentId = ShipmentId::generate();

        ($this->projector)($this->placed(self::ORDER_ID, 1500, '2026-03-01 10:00:00'));
        ($this->projector)($this->shipped($shipmentId, '2026-03-02 08:30:00'));

        $row = $this->row(self::ORDER_ID);
        self::assertSame('shipped', $row['status']);
        self::assertSame($shipmentId->value, $row['shipment_id']);
        self::assertSame(1500, (int) $row['total_amount']);
    }

    public function test_idempotent_projection(): void
    {
        $event = $this->placed(self::OTHER_ORDER_ID, 800, '2026-03-01 12:00:00');

        // Stejná událost dvakrát (at-least-once delivery).
        ($this->projector)($event);
        ($this->projector)($event);

        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM ch05_order_dashboard WHERE order_id = :id',
            ['id' => self::OTHER_ORDER_ID],
        );
        self::assertSame(1, (int) $count);
    }

    public function test_late_redelivery_does_not_roll_back_status(): void
    {
        $placed = $this->placed(self::ORDER_ID, 1500, '2026-03-01 10:00:00');

        ($this->projector)($placed);
        ($this->projector)($this->shipped(ShipmentId::generate(), '2026-03-02 08:30:00'));

        // Stará událost dorazí znovu až po novější. Bez podmínky na
        // updated_at by dashboard tvrdil, že odeslaná objednávka je zase
        // jen přijatá.
        ($this->projector)($placed);

        self::assertSame('shipped', $this->row(self::ORDER_ID)['status']);
    }

    public function test_events_within_the_same_second_keep_their_order(): void
    {
        // Dvě události jednoho agregátu běžně spadnou do téže vteřiny.
        // Se sekundovou přesností by podmínka `<` legitimní přechod zahodila.
        ($this->projector)($this->placed(self::ORDER_ID, 1500, '2026-03-01 10:00:00.100000'));
        ($this->projector)($this->shipped(ShipmentId::generate(), '2026-03-01 10:00:00.200000'));

        self::assertSame('shipped', $this->row(self::ORDER_ID)['status']);
    }

    public function test_cancellation(): void
    {
        ($this->projector)($this->placed(self::ORDER_ID, 1500, '2026-03-01 10:00:00'));
        ($this->projector)(new OrderCancelled(
            orderId: OrderId::fromString(self::ORDER_ID),
            customerId: CustomerId::fromString(self::CUSTOMER_ID),
            reason: 'Zákazník si to rozmyslel',
            occurredAt: new \DateTimeImmutable('2026-03-01 11:00:00'),
        ));

        self::assertSame('cancelled', $this->row(self::ORDER_ID)['status']);
    }
}
