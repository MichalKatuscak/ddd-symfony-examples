<?php

declare(strict_types=1);
namespace App\Chapter08_Testing\Domain\Task;
final readonly class TaskStatus
{
    private function __construct(private string $value) {}
    public static function todo(): self { return new self('todo'); }
    public static function inProgress(): self { return new self('in_progress'); }
    public static function done(): self { return new self('done'); }
    public function isTodo(): bool { return $this->value === 'todo'; }
    public function isDone(): bool { return $this->value === 'done'; }
    public function value(): string { return $this->value; }
}
