<?php

declare(strict_types=1);
namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;

class StoredEvent
{
    private ?int $id = null;

    private string $aggregateId;

    private string $eventClass;

    private array $payload;

    private \DateTimeImmutable $occurredAt;

    public function __construct(string $aggregateId, string $eventClass, array $payload, \DateTimeImmutable $occurredAt)
    {
        $this->aggregateId = $aggregateId;
        $this->eventClass = $eventClass;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public function id(): ?int { return $this->id; }
    public function aggregateId(): string { return $this->aggregateId; }
    public function eventClass(): string { return $this->eventClass; }
    public function payload(): array { return $this->payload; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
