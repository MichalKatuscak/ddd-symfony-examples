<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Shared\Domain\Currency;
use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderConfirmed;
use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderItemAdded;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;

class OrderEventsTest extends TestCase
{
    public function testAddItemRecordsEvent(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::fromString('student-1'));
        $order->addItem(ProductId::generate(), 2, new Money(59900, Currency::CZK));

        $events = $order->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderItemAdded::class, $events[0]);
        $this->assertNotSame('', $events[0]->productId);
        $this->assertSame(2, $events[0]->quantity);
    }

    public function testConfirmRecordsEvent(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::fromString('student-1'));
        $order->addItem(ProductId::generate(), 1, new Money(59900, Currency::CZK));
        $order->releaseEvents(); // clear addItem event

        $order->confirm();
        $events = $order->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderConfirmed::class, $events[0]);
        $this->assertSame(59900, $events[0]->totalAmount);
    }
}
