<?php

declare(strict_types=1);
namespace App\Chapter08_Testing\Domain\Task;

use Symfony\Component\Uid\Uuid;
final readonly class TaskId
{
    public function __construct(public string $value)
    {
        if (empty($value)) throw new \InvalidArgumentException('TaskId cannot be empty');
    }
    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
    }
}
