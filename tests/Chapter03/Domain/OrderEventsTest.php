<?php

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderConfirmed;
use App\Chapter03_BasicConcepts\Domain\Order\Events\OrderItemAdded;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;

class OrderEventsTest extends TestCase
{
    public function testAddItemRecordsEvent(): void
    {
        $order = Order::create(OrderId::generate(), 'student-1');
        $order->addItem('Kniha', 2, new Money(59900, 'CZK'));

        $events = $order->pullEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderItemAdded::class, $events[0]);
        $this->assertSame('Kniha', $events[0]->productName);
        $this->assertSame(2, $events[0]->qty);
    }

    public function testConfirmRecordsEvent(): void
    {
        $order = Order::create(OrderId::generate(), 'student-1');
        $order->addItem('Kniha', 1, new Money(59900, 'CZK'));
        $order->pullEvents(); // clear addItem event

        $order->confirm();
        $events = $order->pullEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderConfirmed::class, $events[0]);
        $this->assertSame(59900, $events[0]->totalAmount);
    }
}
