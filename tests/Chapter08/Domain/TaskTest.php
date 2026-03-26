<?php
namespace App\Tests\Chapter08\Domain;
use App\Chapter08_Testing\Domain\Task\Task;
use App\Chapter08_Testing\Domain\Task\TaskId;
use App\Chapter08_Testing\Domain\Task\TaskAssigned;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function test_new_task_is_todo(): void
    {
        $task = Task::create(TaskId::generate(), 'Implementovat CQRS', 'projekt-1');
        $this->assertTrue($task->status()->isTodo());
    }

    public function test_assigning_task_raises_domain_event(): void
    {
        $task = Task::create(TaskId::generate(), 'Implementovat CQRS', 'projekt-1');
        $task->assignTo('member-42');
        $events = $task->pullEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaskAssigned::class, $events[0]);
        $this->assertSame('member-42', $events[0]->assignedTo);
    }

    public function test_completed_task_cannot_be_reassigned(): void
    {
        $this->expectException(\DomainException::class);
        $task = Task::create(TaskId::generate(), 'Hotový úkol', 'projekt-1');
        $task->assignTo('member-1');
        $task->complete();
        $task->assignTo('member-2');
    }

    public function test_task_title_cannot_be_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Task::create(TaskId::generate(), '', 'projekt-1');
    }
}
