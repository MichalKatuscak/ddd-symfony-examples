<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventSourcing;

/** Řádek Event Store po načtení: payload ještě jako pole. */
final readonly class EventEnvelope
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $eventType,
        public array $payload,
        public int $schemaVersion,
        public int $version,
        public string $occurredOn,
    ) {}
}
