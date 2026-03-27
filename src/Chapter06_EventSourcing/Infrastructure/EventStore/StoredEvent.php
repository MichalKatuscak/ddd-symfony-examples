<?php

declare(strict_types=1);
namespace App\Chapter06_EventSourcing\Infrastructure\EventStore;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ch06_event_store')]
class StoredEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36)]
    private string $aggregateId;

    #[ORM\Column(type: 'string')]
    private string $eventClass;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    public function __construct(string $aggregateId, string $eventClass, array $payload, \DateTimeImmutable $occurredAt)
    {
        $this->aggregateId = $aggregateId;
        $this->eventClass = $eventClass;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public function aggregateId(): string { return $this->aggregateId; }
    public function eventClass(): string { return $this->eventClass; }
    public function payload(): array { return $this->payload; }
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
