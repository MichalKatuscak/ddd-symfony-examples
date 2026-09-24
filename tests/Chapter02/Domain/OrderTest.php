<?php

declare(strict_types=1);

namespace App\Tests\Chapter02\Domain;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderCancelled;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderConfirmed;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderItemAdded;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPaid;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPlaced;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderShipped;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\EmptyOrderException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\OrderLockedBySagaException;
use App\Chapter02_AggregateDesign\Domain\Order\Order;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter02_AggregateDesign\Domain\Order\OrderStatus;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Chapter02_AggregateDesign\Domain\Shipping\ShipmentId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_new_order_starts_as_draft_and_records_order_placed(): void
    {
        $id = OrderId::generate();
        $customerId = CustomerId::generate();
        $order = Order::place($id, $customerId);

        self::assertSame(OrderStatus::Draft, $order->status);
        self::assertNull($order->placedAt);

        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderPlaced::class, $events[0]);
        self::assertTrue($events[0]->orderId->equals($id));
        self::assertTrue($events[0]->customerId->equals($customerId));
    }

    public function test_same_product_twice_increases_quantity(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $productId = ProductId::generate();

        $order->addItem($productId, 2, new Money(10_000, Currency::CZK));
        $order->addItem($productId, 3, new Money(10_000, Currency::CZK));

        // Invariant „jedna položka na produkt“: množství se sčítá.
        self::assertCount(1, $order->items());
        self::assertSame(5, $order->items()[0]->quantity);
        self::assertSame(50_000, $order->totalAmount()->amountInCents);
    }

    public function test_every_add_item_records_event_with_occurred_at(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->releaseEvents();
        $productId = ProductId::generate();

        $order->addItem($productId, 2, new Money(10_000, Currency::CZK));
        $order->addItem($productId, 3, new Money(10_000, Currency::CZK));

        // Událost nese i zvýšení množství u existující položky.
        $events = $order->releaseEvents();
        self::assertCount(2, $events);
        self::assertContainsOnlyInstancesOf(OrderItemAdded::class, $events);
        self::assertSame(3, $events[1]->quantity);
        self::assertInstanceOf(\DateTimeImmutable::class, $events[1]->occurredAt);
    }

    public function test_empty_order_cannot_be_confirmed(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $this->expectException(EmptyOrderException::class);
        $order->confirm();
    }

    public function test_empty_order_has_no_total(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        // Žádná tichá nula v natvrdo zvolené měně.
        $this->expectException(EmptyOrderException::class);
        $order->totalAmount();
    }

    public function test_mixed_currencies_do_not_pass_silently(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));
        $order->addItem(ProductId::generate(), 1, new Money(400, Currency::EUR));

        $this->expectException(\DomainException::class);
        $order->totalAmount();
    }

    public function test_factory_with_first_item_confirms_immediately(): void
    {
        $at = new \DateTimeImmutable('2026-09-01 10:00');
        $order = $this->placedOrder($at);

        self::assertSame(OrderStatus::Confirmed, $order->status);
        self::assertEquals($at, $order->placedAt);
    }

    public function test_factory_with_first_item_records_events_in_order(): void
    {
        $order = $this->placedOrder();

        self::assertSame(
            [OrderPlaced::class, OrderItemAdded::class, OrderConfirmed::class],
            array_map(static fn (object $e): string => $e::class, $order->releaseEvents()),
        );
    }

    public function test_confirmed_order_cannot_be_confirmed_again(): void
    {
        $order = $this->placedOrder();

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->confirm();
    }

    public function test_item_cannot_be_added_outside_draft(): void
    {
        $order = $this->placedOrder();

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->addItem(ProductId::generate(), 1, new Money(100, Currency::CZK));
    }

    public function test_mark_paid_records_order_paid(): void
    {
        $order = $this->placedOrder();
        $order->releaseEvents();

        $order->markPaid();

        self::assertSame(OrderStatus::Paid, $order->status);
        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderPaid::class, $events[0]);
        self::assertTrue($events[0]->orderId->equals($order->id));
    }

    public function test_repeated_mark_paid_is_idempotent(): void
    {
        $order = $this->placedOrder();
        $order->markPaid();
        $order->releaseEvents();

        $order->markPaid(); // opakované doručení příkazu (at-least-once)

        self::assertSame(OrderStatus::Paid, $order->status);
        self::assertSame([], $order->releaseEvents());
    }

    public function test_draft_order_cannot_be_paid(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->markPaid();
    }

    public function test_full_lifecycle_reaches_shipped(): void
    {
        $order = $this->placedOrder();
        $shipmentId = ShipmentId::generate();

        $order->markPaid();
        $order->ship($shipmentId);

        self::assertSame(OrderStatus::Shipped, $order->status);

        // Každá hrana stavového grafu vydává událost.
        $events = $order->releaseEvents();
        self::assertSame(
            [
                OrderPlaced::class,
                OrderItemAdded::class,
                OrderConfirmed::class,
                OrderPaid::class,
                OrderShipped::class,
            ],
            array_map(static fn (object $e): string => $e::class, $events),
        );
        self::assertSame($shipmentId->value, $events[4]->shipmentId->value);
    }

    public function test_repeated_ship_is_idempotent(): void
    {
        $order = $this->placedOrder();
        $order->markPaid();
        $order->ship(ShipmentId::generate());
        $order->releaseEvents();

        $order->ship(ShipmentId::generate());

        self::assertSame(OrderStatus::Shipped, $order->status);
        self::assertSame([], $order->releaseEvents());
    }

    public function test_shipping_unpaid_order_is_refused(): void
    {
        $order = $this->placedOrder();

        // Přechod Confirmed → Shipped v grafu není, takže je zakázaný.
        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->ship(ShipmentId::generate());
    }

    public function test_cancel_records_event_with_reason_and_time(): void
    {
        $order = $this->placedOrder();
        $order->releaseEvents();
        $when = new \DateTimeImmutable('2026-09-01 12:00');

        $order->cancel('Zákazník si to rozmyslel', $when);

        self::assertSame(OrderStatus::Cancelled, $order->status);
        $events = $order->releaseEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderCancelled::class, $events[0]);
        self::assertSame('Zákazník si to rozmyslel', $events[0]->reason);
        self::assertSame($when, $events[0]->occurredAt);
        self::assertTrue($events[0]->customerId->equals($order->customerId));
    }

    public function test_paid_order_can_be_cancelled(): void
    {
        // Kompenzace ságy ruší právě zaplacenou objednávku.
        $order = $this->placedOrder();
        $order->markPaid();

        $order->cancel('kompenzace', new \DateTimeImmutable());

        self::assertSame(OrderStatus::Cancelled, $order->status);
    }

    public function test_cancelling_twice_is_not_an_error(): void
    {
        $order = $this->placedOrder();
        $now = new \DateTimeImmutable();

        $order->cancel('důvod', $now);
        $order->releaseEvents();
        $order->cancel('důvod', $now); // opakování retry ságy

        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertSame([], $order->releaseEvents());
    }

    public function test_shipped_order_cannot_be_cancelled(): void
    {
        $order = $this->placedOrder();
        $order->markPaid();
        $order->ship(ShipmentId::generate());

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->cancel('pozdě', new \DateTimeImmutable());
    }

    public function test_locked_order_refuses_user_cancellation(): void
    {
        $order = $this->placedOrder();
        $order->lockForSaga();

        // Bez zámku by storno prošlo, sága by dál strhla platbu
        // a vytvořila zásilku k objednávce, která už neexistuje.
        $this->expectException(OrderLockedBySagaException::class);
        $order->cancel('teď ne', new \DateTimeImmutable());
    }

    public function test_released_lock_allows_cancellation(): void
    {
        $order = $this->placedOrder();
        $order->lockForSaga();
        $order->releaseSagaLock();

        $order->cancel('po doběhnutí procesu', new \DateTimeImmutable());

        self::assertSame(OrderStatus::Cancelled, $order->status);
    }

    public function test_ownership_is_answered_by_the_aggregate(): void
    {
        $customerId = CustomerId::generate();
        $order = Order::place(OrderId::generate(), $customerId);

        self::assertTrue($order->isOwnedBy(CustomerId::fromString($customerId->value)));
        self::assertFalse($order->isOwnedBy(CustomerId::generate()));
    }

    private function placedOrder(?\DateTimeImmutable $at = null): Order
    {
        return Order::placeWithFirstItem(
            CustomerId::generate(),
            ProductId::generate(),
            1,
            new Money(30_000, Currency::CZK),
            $at,
        );
    }
}
