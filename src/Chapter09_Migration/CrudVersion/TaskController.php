<?php
declare(strict_types=1);
namespace App\Chapter09_Migration\CrudVersion;

final class TaskController
{
    /** @return array{task: Task, error: ?string} */
    public function completeWithoutStart(): array
    {
        $task = new Task();
        $task->setId('task-1');
        $task->setTitle('Refaktorovat controller');
        $task->setStatus('done');
        return ['task' => $task, 'error' => null];
    }

    /** @return array{task: Task, error: ?string} */
    public function setInvalidStatus(): array
    {
        $task = new Task();
        $task->setId('task-2');
        $task->setTitle('Test task');
        $task->setStatus('banana');
        return ['task' => $task, 'error' => null];
    }
}
