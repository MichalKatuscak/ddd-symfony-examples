<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Domain;

use App\Chapter11_OutboxPattern\Domain\Order\Order;
use App\Chapter11_OutboxPattern\Domain\Order\OrderCancelled;
use App\Chapter11_OutboxPattern\Domain\Order\OrderId;
use App\Chapter11_OutboxPattern\Domain\Order\OrderPlaced;
use App\Chapter11_OutboxPattern\Domain\Order\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_place_records_order_placed_event(): void
    {
        $id = OrderId::generate();
        $order = Order::place($id, 'customer-1', 1500);

        $this->assertSame(OrderStatus::Placed, $order->status());
        $this->assertSame(1500, $order->amount());
        $this->assertSame('customer-1', $order->customerId());

        $events = $order->pullEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderPlaced::class, $events[0]);
        $this->assertSame($id->value, $events[0]->orderId);
        $this->assertNotEmpty($events[0]->eventId, 'Event must carry a stable id used for inbox dedup');
    }

    public function test_pull_events_clears_buffer(): void
    {
        $order = Order::place(OrderId::generate(), 'customer-1', 100);
        $order->pullEvents();

        $this->assertSame([], $order->pullEvents());
    }

    public function test_cancel_records_order_cancelled_event(): void
    {
        $order = Order::place(OrderId::generate(), 'customer-1', 200);
        $order->pullEvents(); // discard placed event

        $order->cancel('customer changed mind');

        $events = $order->pullEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderCancelled::class, $events[0]);
        $this->assertSame('customer changed mind', $events[0]->reason);
        $this->assertSame(OrderStatus::Cancelled, $order->status());
    }

    public function test_cannot_cancel_twice(): void
    {
        $order = Order::place(OrderId::generate(), 'customer-1', 200);
        $order->cancel('first');

        $this->expectException(\DomainException::class);
        $order->cancel('second');
    }

    public function test_amount_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Order::place(OrderId::generate(), 'customer-1', 0);
    }
}
