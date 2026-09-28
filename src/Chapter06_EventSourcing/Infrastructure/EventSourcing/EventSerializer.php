<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventSourcing;

use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderConfirmed;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderItemAdded;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderPlaced;
use App\Chapter06_EventSourcing\Ordering\EventSourced\Event\OrderShipped;
use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class EventSerializer
{
    /**
     * @param array<string, class-string<DomainEvent>> $typeMap Mapa eventType na třídu.
     *        Kniha ji zapisuje do services.yaml; ukázka ji nese v atributu,
     *        protože services.yaml sdílejí všechny kapitoly.
     */
    public function __construct(
        #[Autowire([
            'ordering.order_placed'     => OrderPlaced::class,
            'ordering.order_item_added' => OrderItemAdded::class,
            'ordering.order_confirmed'  => OrderConfirmed::class,
            'ordering.order_shipped'    => OrderShipped::class,
        ])]
        private array $typeMap,
    ) {}

    /** @param array<string, mixed> $row Řádek z tabulky event store. */
    public function deserialize(array $row): EventEnvelope
    {
        return new EventEnvelope(
            eventType:     $row['event_type'],
            payload:       json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR),
            schemaVersion: (int) $row['schema_version'],
            version:       (int) $row['version'],
            occurredOn:    $row['occurred_on'],
        );
    }

    /**
     * Obálka na doménovou událost. Kniha payload nejdřív prožene
     * UpcasterChain; ukázka má všechny události ve verzi 1 a upcastery nemá.
     */
    public function toEvent(EventEnvelope $envelope): DomainEvent
    {
        $class = $this->typeMap[$envelope->eventType]
            ?? throw new \RuntimeException(
                "Unknown event type {$envelope->eventType}. Missing entry in typeMap."
            );

        return $class::fromPayload($envelope->payload);
    }
}
