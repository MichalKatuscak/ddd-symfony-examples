<?php

declare(strict_types=1);

namespace App\Tests\Chapter09\CrudVersion;

use App\Chapter09_Migration\CrudVersion\Task as CrudTask;
use App\Chapter09_Migration\Domain\Task\Task as DddTask;
use App\Chapter09_Migration\Domain\Task\TaskId;
use App\Chapter09_Migration\Domain\Task\TaskStatus;
use PHPUnit\Framework\TestCase;

final class CrudComparisonTest extends TestCase
{
    public function testCrudAllowsSkippingStates(): void
    {
        $task = new CrudTask();
        $task->setId('task-1');
        $task->setTitle('Implementovat feature');
        // CRUD allows going directly to 'done' without passing through 'in_progress'
        $task->setStatus('done');

        $this->assertSame('done', $task->getStatus());
    }

    public function testCrudAllowsInvalidStatus(): void
    {
        $task = new CrudTask();
        $task->setId('task-1');
        $task->setTitle('Libovolný úkol');
        // CRUD accepts any string — no validation
        $task->setStatus('banana');

        $this->assertSame('banana', $task->getStatus());
    }

    public function testDddPreventsSkippingStates(): void
    {
        $this->expectException(\DomainException::class);

        $task = DddTask::create(TaskId::generate(), 'Implementovat feature', 'projekt-1');
        // DDD Task requires start() before complete()
        $task->complete();
    }

    public function testDddUsesEnumForStatus(): void
    {
        $task = DddTask::create(TaskId::generate(), 'Implementovat feature', 'projekt-1');
        $task->start('member-1');
        $task->complete();

        $status = $task->status();
        $this->assertInstanceOf(TaskStatus::class, $status);
        $this->assertSame(TaskStatus::Done, $status);
        $this->assertSame('done', $status->value);
    }
}
