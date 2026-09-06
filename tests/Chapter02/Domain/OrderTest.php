<?php

declare(strict_types=1);

namespace App\Tests\Chapter02\Domain;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderCancelled;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderShipped;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\EmptyOrderException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\OrderLockedBySagaException;
use App\Chapter02_AggregateDesign\Domain\Order\Order;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter02_AggregateDesign\Domain\Order\OrderStatus;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Chapter02_AggregateDesign\Domain\Order\ShipmentId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_new_order_starts_as_draft(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        self::assertSame(OrderStatus::Draft, $order->status);
        self::assertNull($order->placedAt);
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

    public function test_empty_order_cannot_be_confirmed(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $this->expectException(EmptyOrderException::class);
        $order->confirm();
    }

    public function test_factory_with_first_item_confirms_immediately(): void
    {
        $order = $this->placedOrder();

        self::assertSame(OrderStatus::Confirmed, $order->status);
        self::assertNotNull($order->placedAt);
    }

    public function test_full_lifecycle_reaches_shipped(): void
    {
        $order = $this->placedOrder();

        $order->markPaid();
        $order->ship(ShipmentId::generate());

        self::assertSame(OrderStatus::Shipped, $order->status);
        self::assertInstanceOf(OrderShipped::class, $order->releaseEvents()[1]);
    }

    public function test_shipping_unpaid_order_is_refused(): void
    {
        $order = $this->placedOrder();

        // Přechod Confirmed → Shipped v grafu není, takže je zakázaný.
        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->ship(ShipmentId::generate());
    }

    public function test_cancel_records_event(): void
    {
        $order = $this->placedOrder();
        $order->releaseEvents();

        $order->cancel('Zákazník si to rozmyslel', new \DateTimeImmutable());

        self::assertSame(OrderStatus::Cancelled, $order->status);
        self::assertInstanceOf(OrderCancelled::class, $order->releaseEvents()[0]);
    }

    public function test_cancelling_twice_is_not_an_error(): void
    {
        $order = $this->placedOrder();
        $now = new \DateTimeImmutable();

        $order->cancel('důvod', $now);
        $order->cancel('důvod', $now); // opakování retry ságy

        self::assertSame(OrderStatus::Cancelled, $order->status);
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

    private function placedOrder(): Order
    {
        return Order::placeWithFirstItem(
            CustomerId::generate(),
            ProductId::generate(),
            1,
            new Money(30_000, Currency::CZK),
        );
    }
}
