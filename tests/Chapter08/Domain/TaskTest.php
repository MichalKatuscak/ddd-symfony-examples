<?php

declare(strict_types=1);
namespace App\Tests\Chapter08\Domain;
use App\Chapter08_Testing\Domain\Task\Task;
use App\Chapter08_Testing\Domain\Task\TaskId;
use App\Chapter08_Testing\Domain\Task\TaskAssigned;
use App\Chapter08_Testing\Domain\Task\TaskStatus;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function test_new_task_is_todo(): void
    {
        $task = Task::create(TaskId::generate(), 'Implementovat CQRS', 'projekt-1');
        $this->assertSame(TaskStatus::Todo, $task->status());
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

    public function testCompleteRequiresInProgress(): void
    {
        $this->expectException(\DomainException::class);
        $task = Task::create(TaskId::generate(), 'Nový úkol', 'projekt-1');
        $task->complete(); // status is Todo, not InProgress
    }

    public function testAssignToChangesStatus(): void
    {
        $task = Task::create(TaskId::generate(), 'Implementovat repozitář', 'projekt-1');
        $task->assignTo('member-5');
        $this->assertSame(TaskStatus::InProgress, $task->status());
        $this->assertSame('member-5', $task->assignedTo());
    }
}
