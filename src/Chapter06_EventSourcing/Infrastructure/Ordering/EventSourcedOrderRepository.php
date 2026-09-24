<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\Ordering;

use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\EventSerializer;
use App\Chapter06_EventSourcing\Infrastructure\EventSourcing\EventStore;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Exception\OrderNotFoundException;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Order;

final class EventSourcedOrderRepository
{
    private const AGGREGATE_TYPE = 'ordering.order';

    public function __construct(
        private readonly EventStore $eventStore,
        private readonly EventSerializer $serializer,
    ) {}

    public function load(string $orderId): Order
    {
        $envelopes = $this->eventStore->loadStream($orderId);

        if ($envelopes === []) {
            throw OrderNotFoundException::withId($orderId);
        }

        $events = array_map(
            fn ($envelope) => $this->serializer->toEvent($envelope),
            $envelopes,
        );

        return Order::reconstituteFromEvents($events);
    }

    public function save(Order $order): void
    {
        $newEvents = $order->recordedEvents();

        if ($newEvents === []) {
            return;
        }

        // expectedVersion = aktuální verze PŘED novými událostmi
        $expectedVersion = $order->version() - count($newEvents);

        $this->eventStore->append(
            $order->orderId(),
            self::AGGREGATE_TYPE,
            $newEvents,
            $expectedVersion,
        );

        // Až teď je zápis jistý. Při ConcurrencyException zůstanou události
        // v agregátu a volající může načíst čerstvý stav a zkusit to znovu.
        $order->releaseEvents();
    }
}
