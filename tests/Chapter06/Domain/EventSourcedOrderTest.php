<?php

namespace App\Tests\Chapter06\Domain;

use App\Chapter06_EventSourcing\Domain\Order\Events\OrderCancelled;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderConfirmed;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderPlaced;
use App\Chapter06_EventSourcing\Domain\Order\Order;
use App\Chapter06_EventSourcing\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;

final class EventSourcedOrderTest extends TestCase
{
    public function test_order_state_reconstructed_from_events(): void
    {
        $id = OrderId::generate();
        $events = [
            new OrderPlaced($id->value, 'zákazník-1', 59900),
            new OrderConfirmed($id->value),
        ];

        $order = Order::reconstruct($id, $events);
        $this->assertSame('confirmed', $order->status());
        $this->assertSame(59900, $order->totalAmount());
    }

    public function test_cancelled_order_has_cancelled_status(): void
    {
        $id = OrderId::generate();
        $order = Order::reconstruct($id, [
            new OrderPlaced($id->value, 'zákazník-1', 10000),
            new OrderCancelled($id->value, 'Zákazník si to rozmyslel'),
        ]);

        $this->assertSame('cancelled', $order->status());
    }
}
