<?php

declare(strict_types=1);
namespace App\Chapter08_Testing\Domain\Task;
final readonly class TaskId
{
    public function __construct(public string $value)
    {
        if (empty($value)) throw new \InvalidArgumentException('TaskId cannot be empty');
    }
    public static function generate(): self
    {
        return new self(\Symfony\Component\Uid\Uuid::v4()->toRfc4122());
    }
}
