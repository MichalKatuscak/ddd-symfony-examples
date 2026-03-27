<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;

interface EventStoreInterface
{
    /** @param DomainEvent[] $events */
    public function append(string $aggregateId, array $events, int $expectedVersion): void;

    /** @return DomainEvent[] */
    public function load(string $aggregateId): array;

    public function countEvents(string $aggregateId): int;
}
