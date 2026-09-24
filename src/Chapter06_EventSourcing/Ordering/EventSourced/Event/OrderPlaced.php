<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Ordering\EventSourced\Event;

use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class OrderPlaced extends DomainEvent
{
    private function __construct(
        string $eventId,
        DateTimeImmutable $occurredAt,
        public readonly string $orderId,
        public readonly string $customerId,
    ) {
        parent::__construct($eventId, $occurredAt);
    }

    /** Pojmenovaný konstruktor pro první vznik události – jen zde se generuje UUID a čas. */
    public static function create(string $orderId, string $customerId): self
    {
        return new self(
            eventId:    (string) Uuid::v7(),
            occurredAt: new DateTimeImmutable('now', new \DateTimeZone('UTC')),
            orderId:    $orderId,
            customerId: $customerId,
        );
    }

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): static
    {
        return new self(
            eventId:    $payload['eventId'],
            // Payload nenese offset, proto UTC uvádíme explicitně.
            occurredAt: new DateTimeImmutable($payload['occurredAt'], new \DateTimeZone('UTC')),
            orderId:    $payload['orderId'],
            customerId: $payload['customerId'],
        );
    }

    public function eventType(): string { return 'ordering.order_placed'; }

    public function schemaVersion(): int { return 1; }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'eventId'    => $this->eventId,
            'occurredAt' => $this->occurredAt->format('Y-m-d H:i:s.u'),
            'orderId'    => $this->orderId,
            'customerId' => $this->customerId,
        ];
    }
}
