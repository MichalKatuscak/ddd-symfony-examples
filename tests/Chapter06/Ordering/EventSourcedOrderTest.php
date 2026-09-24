<?php

declare(strict_types=1);

namespace App\Tests\Chapter06\Ordering;

use App\Chapter06_EventSourcing\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderConfirmed;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderItemAdded;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderPlaced;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderShipped;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Exception\EmptyOrderException;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Exception\InvalidOrderStateTransitionException;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Order;
use App\Chapter06_EventSourcing\Ordering\EventSourced\OrderItem;
use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Given = historické události, when = volání metody, then = nově nahrané události.
 */
final class EventSourcedOrderTest extends TestCase
{
    private string $orderId;

    protected function setUp(): void
    {
        $this->orderId = (string) Uuid::v7();
    }

    public function test_place_records_order_placed_in_draft(): void
    {
        $order = Order::place($this->orderId, 'customer-1');

        self::assertSame([OrderPlaced::class], $this->types($order->recordedEvents()));
        self::assertSame(OrderStatus::Draft, $order->status());
        self::assertSame(1, $order->version());
    }

    public function test_given_draft_with_item_when_confirmed_then_order_confirmed(): void
    {
        $order = Order::reconstituteFromEvents([
            OrderPlaced::create($this->orderId, 'customer-1'),
            OrderItemAdded::create($this->orderId, new OrderItem('product-1', 2, 500)),
        ]);

        $order->confirm();

        self::assertSame([OrderConfirmed::class], $this->types($order->recordedEvents()));
        self::assertSame(OrderStatus::Confirmed, $order->status());
        self::assertSame(3, $order->version());
    }

    public function test_replay_restores_state_without_recording_events(): void
    {
        $order = Order::reconstituteFromEvents([
            OrderPlaced::create($this->orderId, 'customer-1'),
            OrderItemAdded::create($this->orderId, new OrderItem('product-1', 1, 100)),
            OrderConfirmed::create($this->orderId),
            OrderShipped::create($this->orderId, 'DPD-123'),
        ]);

        self::assertSame(OrderStatus::Shipped, $order->status());
        self::assertSame('DPD-123', $order->trackingNumber());
        self::assertSame(4, $order->version());
        self::assertSame([], $order->recordedEvents());
    }

    public function test_empty_order_cannot_be_confirmed(): void
    {
        $order = Order::place($this->orderId, 'customer-1');

        $this->expectException(EmptyOrderException::class);
        $order->confirm();
    }

    public function test_items_can_be_added_only_to_draft(): void
    {
        $order = Order::place($this->orderId, 'customer-1');
        $order->addItem(new OrderItem('product-1', 1, 100));
        $order->confirm();

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->addItem(new OrderItem('product-2', 1, 100));
    }

    public function test_only_confirmed_order_can_be_shipped(): void
    {
        $order = Order::place($this->orderId, 'customer-1');

        $this->expectException(InvalidOrderStateTransitionException::class);
        $order->ship('DPD-123');
    }

    public function test_apply_methods_are_protected(): void
    {
        // Base class je volá dynamicky z vlastního kontextu – private by nešlo.
        foreach (['applyOrderPlaced', 'applyOrderItemAdded', 'applyOrderConfirmed', 'applyOrderShipped'] as $method) {
            self::assertTrue((new \ReflectionMethod(Order::class, $method))->isProtected(), $method);
        }
    }

    public function test_unknown_event_is_rejected_on_replay(): void
    {
        $unknown = new class ((string) Uuid::v7(), new \DateTimeImmutable()) extends DomainEvent {
            public function eventType(): string { return 'ordering.order_lost'; }
            public function schemaVersion(): int { return 1; }
            public function toPayload(): array { return []; }
            public static function fromPayload(array $payload): static { throw new \LogicException(); }
        };

        $this->expectException(\LogicException::class);
        Order::reconstituteFromEvents([$unknown]);
    }

    public function test_event_types_follow_context_noun_verb_format(): void
    {
        self::assertSame('ordering.order_placed', OrderPlaced::create($this->orderId, 'c')->eventType());
        self::assertSame('ordering.order_item_added', OrderItemAdded::create($this->orderId, new OrderItem('p', 1, 1))->eventType());
        self::assertSame('ordering.order_confirmed', OrderConfirmed::create($this->orderId)->eventType());
        self::assertSame('ordering.order_shipped', OrderShipped::create($this->orderId, 'T')->eventType());
    }

    public function test_payload_round_trip_keeps_identity_and_time(): void
    {
        $event = OrderItemAdded::create($this->orderId, new OrderItem('product-1', 3, 250));

        $restored = OrderItemAdded::fromPayload($event->toPayload());

        self::assertSame($event->eventId, $restored->eventId);
        self::assertEquals($event->occurredAt, $restored->occurredAt);
        self::assertEquals($event->item, $restored->item);
    }

    /**
     * @param list<object> $events
     * @return list<class-string>
     */
    private function types(array $events): array
    {
        return array_map(static fn (object $e): string => $e::class, $events);
    }
}
