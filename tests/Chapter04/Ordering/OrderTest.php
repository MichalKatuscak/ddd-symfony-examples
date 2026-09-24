<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\Ordering;

use App\Chapter04_Implementation\Ordering\Domain\Event\OrderPlaced;
use App\Chapter04_Implementation\Ordering\Domain\Event\OrderStatusChanged;
use App\Chapter04_Implementation\Ordering\Domain\Exception\InvalidOrderStateTransitionException;
use App\Chapter04_Implementation\Ordering\Domain\Model\Order;
use App\Chapter04_Implementation\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderId;
use App\Chapter04_Implementation\Ordering\Domain\ValueObject\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_place_starts_in_draft_and_records_order_placed(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $events = $order->releaseEvents();

        self::assertSame(OrderStatus::Draft, $order->status());
        self::assertCount(1, $events);
        self::assertInstanceOf(OrderPlaced::class, $events[0]);
        self::assertTrue($order->id->equals($events[0]->orderId));
    }

    public function test_allowed_transition_records_status_changed(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->releaseEvents();

        $order->transitionTo(OrderStatus::Confirmed);

        $events = $order->releaseEvents();
        self::assertSame(OrderStatus::Confirmed, $order->status());
        self::assertInstanceOf(OrderStatusChanged::class, $events[0]);
        self::assertSame(OrderStatus::Draft, $events[0]->from);
        self::assertSame(OrderStatus::Confirmed, $events[0]->to);
    }

    public function test_forbidden_transition_throws_named_exception(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());

        $this->expectException(InvalidOrderStateTransitionException::class);
        $this->expectExceptionMessage('Nelze přejít ze stavu „draft“ do stavu „shipped“.');

        $order->transitionTo(OrderStatus::Shipped);
    }
}
