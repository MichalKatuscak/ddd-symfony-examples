<?php

declare(strict_types=1);
namespace App\Chapter09_Migration\Domain\Task;

use App\Chapter09_Migration\Domain\Task\Exception\InvalidTaskStateTransitionException;
final class Task
{
    private TaskStatus $status;
    private ?string $assignedTo = null;

    private function __construct(
        public readonly TaskId $id,
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
            throw InvalidTaskStateTransitionException::cannotStart();
        }
        $this->assignedTo = $memberId;
        $this->status = TaskStatus::InProgress;
    }

    public function complete(): void
    {
        if ($this->status !== TaskStatus::InProgress) {
            throw InvalidTaskStateTransitionException::cannotComplete();
        }
        $this->status = TaskStatus::Done;
    }

    public function title(): string { return $this->title; }
    public function status(): TaskStatus { return $this->status; }
    public function assignedTo(): ?string { return $this->assignedTo; }
}
