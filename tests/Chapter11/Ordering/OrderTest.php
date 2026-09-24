<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Ordering;

use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderCancelled;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderConfirmed;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderItemAdded;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderPaid;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderPlaced;
use App\Chapter11_OutboxPattern\Ordering\Domain\Event\OrderShipped;
use App\Chapter11_OutboxPattern\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Chapter11_OutboxPattern\Ordering\Domain\Exception\OrderLockedBySagaException;
use App\Chapter11_OutboxPattern\Ordering\Domain\Model\Order;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter11_OutboxPattern\Shipping\Domain\ValueObject\ShipmentId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class OrderTest extends TestCase
{
    public function test_place_with_items_starts_with_place_and_ends_confirmed(): void
    {
        $order = $this->placeWithTwoItems();

        $events = array_map(static fn (object $e): string => $e::class, $order->releaseEvents());

        // place() nahraje OrderPlaced jako první, confirm() OrderConfirmed jako poslední.
        self::assertSame([
            OrderPlaced::class,
            OrderItemAdded::class,
            OrderItemAdded::class,
            OrderConfirmed::class,
        ], $events);
        self::assertSame(OrderStatus::Confirmed, $order->status);
        self::assertSame(2 * 750_00 + 1 * 199_00, $order->totalAmount()->amountInCents);
    }

    public function test_place_with_items_locks_order_for_saga(): void
    {
        $order = $this->placeWithTwoItems();

        self::assertTrue($order->isLockedBySaga());

        $this->expectException(OrderLockedBySagaException::class);
        $order->cancel('Zákazník si to rozmyslel', new \DateTimeImmutable());
    }

    public function test_mark_paid_is_idempotent(): void
    {
        $order = $this->placeWithTwoItems();
        $order->releaseEvents();

        $order->markPaid();
        $order->markPaid(); // opakované doručení MarkOrderPaid

        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderPaid::class, $events[0]);
        self::assertSame(OrderStatus::Paid, $order->status);
    }

    public function test_ship_is_idempotent(): void
    {
        $order = $this->placeWithTwoItems();
        $order->markPaid();
        $order->releaseEvents();

        $shipmentId = ShipmentId::generate();
        $order->ship($shipmentId);
        $order->ship($shipmentId);

        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderShipped::class, $events[0]);
        self::assertSame(OrderStatus::Shipped, $order->status);
    }

    public function test_cannot_mark_draft_order_paid(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->markPaid();
    }

    public function test_paid_order_can_be_cancelled_once_lock_is_released(): void
    {
        $order = $this->placeWithTwoItems();
        $order->markPaid();
        $order->releaseSagaLock();
        $order->releaseEvents();

        $when = new \DateTimeImmutable('2026-09-24 12:00:00');
        $order->cancel('Platba vrácena', $when);
        $order->cancel('Opakované storno', $when); // idempotentní větev

        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderCancelled::class, $events[0]);
        self::assertSame('Platba vrácena', $events[0]->reason);
        self::assertSame($when, $events[0]->occurredAt);
        self::assertSame(OrderStatus::Cancelled, $order->status);
    }

    public function test_shipped_order_cannot_be_cancelled(): void
    {
        $order = $this->placeWithTwoItems();
        $order->markPaid();
        $order->ship(ShipmentId::generate());
        $order->releaseSagaLock();

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->cancel('Pozdě', new \DateTimeImmutable());
    }

    private function placeWithTwoItems(): Order
    {
        return Order::placeWithItems(CustomerId::generate(), [
            ['productId' => (string) Uuid::v7(), 'quantity' => 2, 'unitPriceInCents' => 750_00],
            ['productId' => (string) Uuid::v7(), 'quantity' => 1, 'unitPriceInCents' => 199_00],
        ]);
    }
}
