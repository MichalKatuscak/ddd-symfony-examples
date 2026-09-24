<?php

declare(strict_types=1);

namespace App\Tests\Chapter06\Infrastructure;

use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\ConcurrencyException;
use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\DoctrineEventStore;
use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\EventSerializer;
use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\RequestEventMetadataProvider;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderConfirmed;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderItemAdded;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderPlaced;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderShipped;
use App\Chapter06_EventSourcing\Ordering\EventSourced\OrderItem;
use App\Tests\Chapter06\EventStoreSchema;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DoctrineEventStoreTest extends TestCase
{
    private Connection $connection;
    private EventSerializer $serializer;
    private RequestEventMetadataProvider $metadata;
    private DoctrineEventStore $store;
    private string $orderId;

    protected function setUp(): void
    {
        $this->connection = EventStoreSchema::connection();
        $this->serializer = new EventSerializer([
            'ordering.order_placed' => OrderPlaced::class,
            'ordering.order_item_added' => OrderItemAdded::class,
            'ordering.order_confirmed' => OrderConfirmed::class,
            'ordering.order_shipped' => OrderShipped::class,
        ]);
        $this->metadata = new RequestEventMetadataProvider();
        $this->store = new DoctrineEventStore($this->connection, $this->serializer, $this->metadata);
        $this->orderId = (string) Uuid::v7();
    }

    public function test_appended_events_are_loaded_in_stream_order(): void
    {
        $placed = OrderPlaced::create($this->orderId, 'customer-1');
        $item = OrderItemAdded::create($this->orderId, new OrderItem('product-1', 2, 500));

        $this->store->append($this->orderId, 'ordering.order', [$placed, $item], 0);

        $stream = $this->store->loadStream($this->orderId);
        self::assertSame(['ordering.order_placed', 'ordering.order_item_added'], array_map(static fn ($e) => $e->eventType, $stream));
        self::assertSame([1, 2], array_map(static fn ($e) => $e->version, $stream));

        // Identita i čas se po deserializaci přebírají z payloadu, negenerují se znovu.
        $restored = $this->serializer->toEvent($stream[0]);
        self::assertSame($placed->eventId, $restored->eventId);
        self::assertEquals($placed->occurredAt, $restored->occurredAt);
    }

    public function test_load_stream_from_version(): void
    {
        $this->store->append($this->orderId, 'ordering.order', [
            OrderPlaced::create($this->orderId, 'customer-1'),
            OrderItemAdded::create($this->orderId, new OrderItem('product-1', 1, 100)),
            OrderConfirmed::create($this->orderId),
        ], 0);

        $tail = $this->store->loadStream($this->orderId, fromVersion: 2);

        self::assertSame([2, 3], array_map(static fn ($e) => $e->version, $tail));
    }

    public function test_conflicting_append_throws_and_writes_nothing(): void
    {
        $this->store->append($this->orderId, 'ordering.order', [OrderPlaced::create($this->orderId, 'customer-1')], 0);
        $this->store->append($this->orderId, 'ordering.order', [OrderItemAdded::create($this->orderId, new OrderItem('a', 1, 1))], 1);

        // Druhý proces četl stream ve verzi 1 a zapisuje dvě události.
        try {
            $this->store->append($this->orderId, 'ordering.order', [
                OrderItemAdded::create($this->orderId, new OrderItem('b', 1, 1)),
                OrderConfirmed::create($this->orderId),
            ], 1);
            self::fail('Konflikt verzí musí skončit ConcurrencyException.');
        } catch (ConcurrencyException) {
        }

        self::assertCount(2, $this->store->loadStream($this->orderId));
    }

    public function test_metadata_carries_correlation_id(): void
    {
        $this->metadata->bind('corr-1', 'cause-1', 'user-1');

        $this->store->append($this->orderId, 'ordering.order', [OrderPlaced::create($this->orderId, 'customer-1')], 0);

        $metadata = json_decode((string) $this->connection->fetchOne('SELECT metadata FROM ch06_event_store'), true);
        self::assertSame(['correlationId' => 'corr-1', 'causationId' => 'cause-1', 'userId' => 'user-1'], $metadata);
    }

    public function test_load_all_iterates_in_batches(): void
    {
        foreach (range(1, 5) as $i) {
            $id = (string) Uuid::v7();
            $this->store->append($id, 'ordering.order', [OrderPlaced::create($id, 'customer-' . $i)], 0);
        }

        $all = iterator_to_array($this->store->loadAll(batchSize: 2), preserve_keys: false);

        self::assertCount(5, $all);
    }
}
