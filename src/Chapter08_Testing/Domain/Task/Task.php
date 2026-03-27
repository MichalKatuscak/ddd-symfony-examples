<?php

declare(strict_types=1);
namespace App\Chapter08_Testing\Domain\Task;
use App\Shared\Domain\AggregateRoot;

final class Task extends AggregateRoot
{
    private TaskStatus $status;
    private ?string $assignedTo = null;

    private function __construct(
        private readonly TaskId $id,
        private readonly string $title,
        private readonly string $projectId,
    ) {
        if (empty($title)) {
            throw new \InvalidArgumentException('Task title cannot be empty');
        }
        $this->status = TaskStatus::Todo;
    }

    public static function create(TaskId $id, string $title, string $projectId): self
    {
        return new self($id, $title, $projectId);
    }

    public function assignTo(string $memberId): void
    {
        if ($this->status === TaskStatus::Done) {
            throw new \DomainException('Cannot reassign a completed task');
        }
        $this->assignedTo = $memberId;
        $this->status = TaskStatus::InProgress;
        $this->record(new TaskAssigned($this->id->value, $memberId));
    }

    public function complete(): void
    {
        if ($this->status !== TaskStatus::InProgress) {
            throw new \DomainException('Task must be in progress before completing');
        }
        $this->status = TaskStatus::Done;
    }

    public function id(): TaskId { return $this->id; }
    public function title(): string { return $this->title; }
    public function status(): TaskStatus { return $this->status; }
    public function assignedTo(): ?string { return $this->assignedTo; }
}
