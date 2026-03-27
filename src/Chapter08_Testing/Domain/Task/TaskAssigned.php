<?php

declare(strict_types=1);
namespace App\Chapter08_Testing\Domain\Task;
use App\Shared\Domain\DomainEvent;
final readonly class TaskAssigned implements DomainEvent
{
    public function __construct(
        public string $taskId,
        public string $assignedTo,
        private \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}
    public function occurredAt(): \DateTimeImmutable { return $this->occurredAt; }
}
