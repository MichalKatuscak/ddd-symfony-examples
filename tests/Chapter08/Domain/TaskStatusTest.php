<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\Domain;

use App\Chapter08_Testing\Domain\Task\TaskStatus;
use PHPUnit\Framework\TestCase;

final class TaskStatusTest extends TestCase
{
    public function testHasTodoCasee(): void
    {
        $this->assertSame('todo', TaskStatus::Todo->value);
    }

    public function testHasInProgressCase(): void
    {
        $this->assertSame('in_progress', TaskStatus::InProgress->value);
    }

    public function testHasDoneCase(): void
    {
        $this->assertSame('done', TaskStatus::Done->value);
    }

    public function testFromWorksForValidValues(): void
    {
        $this->assertSame(TaskStatus::Todo, TaskStatus::from('todo'));
        $this->assertSame(TaskStatus::InProgress, TaskStatus::from('in_progress'));
        $this->assertSame(TaskStatus::Done, TaskStatus::from('done'));
    }
}
