<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\Ordering\Domain\Model;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderConfirmed;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderItemAdded;
use App\Chapter02_AggregateDesign\Domain\Order\Event\OrderPlaced;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\EmptyOrderException;
use App\Chapter02_AggregateDesign\Domain\Order\Exception\InvalidOrderStateTransitionException;
use App\Chapter02_AggregateDesign\Domain\Order\Order;
use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter02_AggregateDesign\Domain\Order\OrderStatus;
use App\Chapter02_AggregateDesign\Domain\Order\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

/**
 * Testy míří na kanonický Order z kapitoly Návrh agregátu
 * (ukázka Chapter02_AggregateDesign), ne na vlastní kopii.
 */
final class OrderTest extends TestCase
{
    public function testAddsItemToOrder(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $order->addItem(ProductId::generate(), 2, new Money(49900, Currency::CZK));

        $events = $order->releaseEvents();

        $this->assertSame(99800, $order->totalAmount()->amountInCents); // 49 900 × 2
        $this->assertSame(Currency::CZK, $order->totalAmount()->currency);
        $this->assertInstanceOf(OrderItemAdded::class, $events[1]);     // [0] je OrderPlaced
        $this->assertSame(2, $events[1]->quantity);
    }

    public function testThrowsExceptionWhenConfirmingEmptyOrder(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $this->expectException(EmptyOrderException::class);

        $order->confirm();
    }

    public function testConfirmsOrderSuccessfully(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));

        $order->confirm();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
    }

    public function testThrowsExceptionWhenConfirmingAlreadyConfirmedOrder(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));
        $order->confirm();

        $this->expectException(InvalidOrderStateTransitionException::class);

        $order->confirm();
    }

    public function testReleasesRecordedEventsInOrder(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));
        $order->confirm();

        $events = $order->releaseEvents();

        $this->assertCount(3, $events); // OrderPlaced + OrderItemAdded + OrderConfirmed
        $this->assertInstanceOf(OrderPlaced::class, $events[0]);
        $this->assertInstanceOf(OrderItemAdded::class, $events[1]);
        $this->assertInstanceOf(OrderConfirmed::class, $events[2]);
    }
}
