<?php

declare(strict_types=1);

namespace App\Tests\Chapter06\Domain;

use App\Chapter06_EventSourcing\Domain\Order\Events\OrderCancelled;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderConfirmed;
use App\Chapter06_EventSourcing\Domain\Order\Events\OrderPlaced;
use App\Chapter06_EventSourcing\Domain\Order\Order;
use App\Chapter06_EventSourcing\Domain\Order\OrderId;
use App\Shared\Domain\DomainEvent;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testPlaceOrderRecordsEvent(): void
    {
        $id = OrderId::generate();
        $order = Order::place($id, 'zákazník-1', 59900);

        $events = $order->pullUncommittedEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderPlaced::class, $events[0]);
        $this->assertSame('zákazník-1', $events[0]->customerId);
        $this->assertSame(59900, $events[0]->totalAmount);
    }

    public function testConfirmRecordsEvent(): void
    {
        $id = OrderId::generate();
        $order = Order::place($id, 'zákazník-1', 59900);
        $order->pullUncommittedEvents(); // clear placement event

        $order->confirm();

        $events = $order->pullUncommittedEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderConfirmed::class, $events[0]);
        $this->assertSame('confirmed', $order->status());
    }

    public function testCancelRecordsEvent(): void
    {
        $id = OrderId::generate();
        $order = Order::place($id, 'zákazník-1', 59900);
        $order->pullUncommittedEvents();

        $order->cancel('Zákazník si to rozmyslel');

        $events = $order->pullUncommittedEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(OrderCancelled::class, $events[0]);
        $this->assertSame('cancelled', $order->status());
    }

    public function testCannotConfirmCancelledOrder(): void
    {
        $this->expectException(\DomainException::class);

        $id = OrderId::generate();
        $order = Order::place($id, 'zákazník-1', 59900);
        $order->cancel('důvod');
        $order->confirm();
    }

    public function testCannotCancelConfirmedOrder(): void
    {
        $this->expectException(\DomainException::class);

        $id = OrderId::generate();
        $order = Order::place($id, 'zákazník-1', 59900);
        $order->confirm();
        $order->cancel('příliš pozdě');
    }

    public function testPullUncommittedEventsClears(): void
    {
        $id = OrderId::generate();
        $order = Order::place($id, 'zákazník-1', 59900);

        $first = $order->pullUncommittedEvents();
        $second = $order->pullUncommittedEvents();

        $this->assertCount(1, $first);
        $this->assertCount(0, $second);
    }

    public function testUnknownEventThrowsLogicException(): void
    {
        $this->expectException(\LogicException::class);

        $unknownEvent = new class implements DomainEvent {
            public function occurredAt(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        };

        $id = OrderId::generate();
        Order::reconstruct($id, [$unknownEvent]);
    }
}
