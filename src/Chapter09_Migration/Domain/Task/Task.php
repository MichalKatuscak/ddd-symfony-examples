<?php

declare(strict_types=1);
namespace App\Chapter09_Migration\Domain\Task;

final class Task
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

    public function start(string $memberId): void
    {
        if ($this->status !== TaskStatus::Todo) {
            throw new \DomainException('Task is already started or done');
        }
        $this->assignedTo = $memberId;
        $this->status = TaskStatus::InProgress;
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
