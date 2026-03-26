<?php
namespace App\Tests\Chapter09\Domain;
use App\Chapter09_Migration\Domain\Task\Task;
use App\Chapter09_Migration\Domain\Task\TaskId;
use PHPUnit\Framework\TestCase;

final class TaskMigrationTest extends TestCase
{
    public function test_ddd_task_encapsulates_status_transition(): void
    {
        $task = Task::create(TaskId::generate(), 'Refaktorovat controller', 'projekt-1');
        $task->start('member-1');
        $this->assertTrue($task->status()->isInProgress());
        $this->assertSame('member-1', $task->assignedTo());
    }

    public function test_ddd_task_prevents_invalid_transition(): void
    {
        $this->expectException(\DomainException::class);
        $task = Task::create(TaskId::generate(), 'Hotový úkol', 'projekt-1');
        $task->complete(); // cannot complete without starting
    }
}
