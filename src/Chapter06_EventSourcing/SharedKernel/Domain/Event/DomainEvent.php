<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\SharedKernel\Domain\Event;

use DateTimeImmutable;

/**
 * Společná bázová třída pro události event-sourced agregátu. Identita a čas
 * jsou public readonly vlastnosti (přímý přístup `$event->eventId`,
 * `$event->occurredAt`); serializaci do Event Store a zpět řeší metody.
 */
abstract class DomainEvent
{
    public function __construct(
        /** Unikátní identifikátor události (UUID v7). */
        public readonly string $eventId,
        /** Čas vzniku události – vždy UTC. */
        public readonly DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Název události pro uložení a vyhledání v Event Store.
     * Formát: <bounded_context>.<podstatné_jméno>_<sloveso_v_minulém_čase>.
     */
    abstract public function eventType(): string;

    /** Verze schématu payloadu – pro upcasting starých událostí. */
    abstract public function schemaVersion(): int;

    /**
     * Serializace do pole pro Event Store, VČETNĚ eventId a occurredAt –
     * identita události je součástí payloadu.
     *
     * @return array<string, mixed>
     */
    abstract public function toPayload(): array;

    /**
     * Rekonstrukce z payloadu. Nesmí generovat nové UUID ani čas –
     * obojí přebírá z payloadu.
     *
     * @param array<string, mixed> $payload
     */
    abstract public static function fromPayload(array $payload): static;
}
