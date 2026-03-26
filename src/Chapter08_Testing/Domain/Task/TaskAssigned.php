<?php
namespace App\Chapter08_Testing\Domain\Task;
use App\Shared\Domain\DomainEvent;
final readonly class TaskAssigned implements DomainEvent
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $assignedTo,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
