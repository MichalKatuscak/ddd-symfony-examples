<?php

declare(strict_types=1);

namespace App\Tests\Chapter06\Infrastructure;

use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\ConcurrencyException;
use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\DoctrineEventStore;
use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\EventSerializer;
use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\RequestEventMetadataProvider;
use App\Chapter06_EventSourcing\Infrastructure\Ordering\EventSourcedOrderRepository;
use App\Chapter06_EventSourcing\Ordering\Domain\ValueObject\OrderStatus;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderConfirmed;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderItemAdded;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderPlaced;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderShipped;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Exception\OrderNotFoundException;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Order;
use App\Chapter06_EventSourcing\Ordering\EventSourced\OrderItem;
use App\Tests\Chapter06\EventStoreSchema;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class EventSourcedOrderRepositoryTest extends TestCase
{
    private EventSourcedOrderRepository $repository;

    protected function setUp(): void
    {
        $serializer = new EventSerializer([
            'ordering.order_placed' => OrderPlaced::class,
            'ordering.order_item_added' => OrderItemAdded::class,
            'ordering.order_confirmed' => OrderConfirmed::class,
            'ordering.order_shipped' => OrderShipped::class,
        ]);
        $store = new DoctrineEventStore(EventStoreSchema::connection(), $serializer, new RequestEventMetadataProvider());
        $this->repository = new EventSourcedOrderRepository($store, $serializer);
    }

    public function test_saved_order_is_rebuilt_from_its_stream(): void
    {
        $orderId = (string) Uuid::v7();
        $order = Order::place($orderId, 'customer-1');
        $order->addItem(new OrderItem('product-1', 2, 500));
        $this->repository->save($order);

        self::assertSame([], $order->recordedEvents());

        $loaded = $this->repository->load($orderId);
        $loaded->confirm();
        $this->repository->save($loaded);

        $again = $this->repository->load($orderId);
        self::assertSame(OrderStatus::Confirmed, $again->status());
        self::assertSame(3, $again->version());
        self::assertCount(1, $again->items());
    }

    public function test_concurrent_save_fails_and_keeps_recorded_events(): void
    {
        $orderId = (string) Uuid::v7();
        $order = Order::place($orderId, 'customer-1');
        $this->repository->save($order);

        $first = $this->repository->load($orderId);
        $second = $this->repository->load($orderId);

        $first->addItem(new OrderItem('product-1', 1, 100));
        $this->repository->save($first);

        $second->addItem(new OrderItem('product-2', 1, 100));

        try {
            $this->repository->save($second);
            self::fail('Druhý zápis ve stejné verzi musí selhat.');
        } catch (ConcurrencyException) {
        }

        // Události zůstaly v agregátu – volající může načíst čerstvý stav a zkusit to znovu.
        self::assertCount(1, $second->recordedEvents());
    }

    public function test_missing_stream_is_reported(): void
    {
        $this->expectException(OrderNotFoundException::class);
        $this->repository->load((string) Uuid::v7());
    }
}
