<?php
declare(strict_types=1);
namespace App\Chapter09_Migration\CrudVersion;

final class Task
{
    private string $id = '';
    private string $title = '';
    private string $status = 'todo';
    private ?string $assignedTo = null;

    public function getId(): string { return $this->id; }
    public function setId(string $id): void { $this->id = $id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getAssignedTo(): ?string { return $this->assignedTo; }
    public function setAssignedTo(?string $a): void { $this->assignedTo = $a; }
}
