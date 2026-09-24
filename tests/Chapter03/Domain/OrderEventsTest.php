<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\Event\OrderConfirmed;
use App\Chapter03_BasicConcepts\Domain\Order\Event\OrderItemAdded;
use App\Chapter03_BasicConcepts\Domain\Order\Event\OrderPlaced;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class OrderEventsTest extends TestCase
{
    public function test_place_records_order_placed(): void
    {
        $id = OrderId::generate();
        $customerId = CustomerId::generate();

        $events = Order::place($id, $customerId)->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OrderPlaced::class, $events[0]);
        self::assertTrue($events[0]->orderId->equals($id));
        self::assertTrue($events[0]->customerId->equals($customerId));
        self::assertInstanceOf(\DateTimeImmutable::class, $events[0]->occurredAt);
    }

    public function test_add_item_records_order_item_added(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->releaseEvents();
        $productId = ProductId::generate();

        $order->addItem($productId, 2, new Money(59_900, Currency::CZK));
        $events = $order->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OrderItemAdded::class, $events[0]);
        self::assertTrue($events[0]->orderId->equals($order->id));
        self::assertTrue($events[0]->productId->equals($productId));
        self::assertSame(2, $events[0]->quantity);
    }

    public function test_confirm_records_order_confirmed(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(59_900, Currency::CZK));
        $order->releaseEvents();

        $order->confirm();
        $events = $order->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(OrderConfirmed::class, $events[0]);
        self::assertTrue($events[0]->customerId->equals($order->customerId));
    }

    public function test_events_keep_the_order_of_domain_operations(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(100, Currency::CZK));
        $order->confirm();

        self::assertSame(
            [OrderPlaced::class, OrderItemAdded::class, OrderConfirmed::class],
            array_map(static fn (object $e): string => $e::class, $order->releaseEvents()),
        );
        // Fronta je po vyzvednutí prázdná – události se publikují jednou.
        self::assertSame([], $order->releaseEvents());
    }

    public function test_failed_rule_records_nothing(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->releaseEvents();

        try {
            $order->confirm();
        } catch (\DomainException) {
        }

        self::assertSame([], $order->releaseEvents());
    }

    public function test_cancel_records_no_event_in_this_basic_form(): void
    {
        // Podoba ze Základních konceptů událost OrderCancelled nemá;
        // tu přidává až Návrh agregátu (Chapter02_AggregateDesign).
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->releaseEvents();

        $order->cancel('důvod', new \DateTimeImmutable());

        self::assertSame([], $order->releaseEvents());
    }
}
