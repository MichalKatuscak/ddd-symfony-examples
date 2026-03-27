<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\Domain\Task;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
}
