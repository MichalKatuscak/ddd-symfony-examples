<?php

declare(strict_types=1);

namespace App\Chapter08_Testing\Domain\Task;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
}
