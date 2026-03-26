<?php
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
        $this->status = TaskStatus::todo();
    }

    public static function create(TaskId $id, string $title, string $projectId): self
    {
        return new self($id, $title, $projectId);
    }

    public function start(string $memberId): void
    {
        if (!$this->status->isTodo()) {
            throw new \DomainException('Task is already started or done');
        }
        $this->assignedTo = $memberId;
        $this->status = TaskStatus::inProgress();
    }

    public function complete(): void
    {
        if (!$this->status->isInProgress()) {
            throw new \DomainException('Task must be in progress before completing');
        }
        $this->status = TaskStatus::done();
    }

    public function id(): TaskId { return $this->id; }
    public function title(): string { return $this->title; }
    public function status(): TaskStatus { return $this->status; }
    public function assignedTo(): ?string { return $this->assignedTo; }
}
