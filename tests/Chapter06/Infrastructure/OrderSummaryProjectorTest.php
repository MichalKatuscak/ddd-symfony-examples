<?php

declare(strict_types=1);

namespace App\Tests\Chapter06\Infrastructure;

use App\Chapter06_EventSourcing\Infrastructure\Ordering\Projection\OrderSummaryProjector;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderConfirmed;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderItemAdded;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderPlaced;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderShipped;
use App\Chapter06_EventSourcing\Ordering\EventSourced\OrderItem;
use App\Tests\Chapter06\EventStoreSchema;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrderSummaryProjectorTest extends TestCase
{
    public function test_projection_follows_order_lifecycle(): void
    {
        $connection = EventStoreSchema::connection();
        $projector = new OrderSummaryProjector($connection);
        $orderId = (string) Uuid::v7();

        $projector->handleOrderPlaced(OrderPlaced::create($orderId, 'customer-1'));
        $projector->handleOrderItemAdded(OrderItemAdded::create($orderId, new OrderItem('p-1', 3, 250)));
        $projector->handleOrderItemAdded(OrderItemAdded::create($orderId, new OrderItem('p-2', 1, 100)));
        $projector->handleOrderConfirmed(OrderConfirmed::create($orderId));
        $projector->handleOrderShipped(OrderShipped::create($orderId, 'DPD-1'));

        $row = $connection->fetchAssociative('SELECT * FROM ch06_order_summary WHERE order_id = ?', [$orderId]);

        self::assertSame('shipped', $row['status']);
        self::assertSame(2, (int) $row['item_count']);
        // Cena řádku je množství krát jednotková cena: 3 × 250 + 1 × 100.
        self::assertSame(850, (int) $row['total_amount']);
        self::assertSame('DPD-1', $row['tracking_no']);
        self::assertNotNull($row['shipped_at']);
    }
}
