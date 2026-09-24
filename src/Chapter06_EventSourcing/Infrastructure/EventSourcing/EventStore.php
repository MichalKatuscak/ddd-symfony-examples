<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventSourcing;

use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;

interface EventStore
{
    /**
     * Uloží nové události do event streamu agregátu.
     *
     * @param list<DomainEvent> $events
     * @param int               $expectedVersion Verze posledního uloženého eventu – slouží
     *                                           pro optimistic locking. 0 pro nový agregát.
     *
     * @throws ConcurrencyException Pokud $expectedVersion neodpovídá skutečné verzi streamu.
     */
    public function append(
        string $aggregateId,
        string $aggregateType,
        array $events,
        int $expectedVersion,
    ): void;

    /**
     * Načte celý event stream agregátu (nebo od dané verze).
     *
     * @return list<EventEnvelope>
     */
    public function loadStream(
        string $aggregateId,
        int $fromVersion = 1,
    ): array;

    /**
     * Načte všechny události z celého Event Store (pro rebuild projekcí).
     *
     * @return \Generator<EventEnvelope>
     */
    public function loadAll(int $batchSize = 500): \Generator;
}
