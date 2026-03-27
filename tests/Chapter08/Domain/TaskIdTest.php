<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\Domain;

use App\Chapter08_Testing\Domain\Task\TaskId;
use PHPUnit\Framework\TestCase;

final class TaskIdTest extends TestCase
{
    public function testValidCreation(): void
    {
        $id = new TaskId('some-valid-id');
        $this->assertSame('some-valid-id', $id->value);
    }

    public function testEmptyThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TaskId('');
    }

    public function testGenerateReturnsUniqueIds(): void
    {
        $id1 = TaskId::generate();
        $id2 = TaskId::generate();

        $this->assertNotSame($id1->value, $id2->value);
        $this->assertNotEmpty($id1->value);
        $this->assertNotEmpty($id2->value);
    }
}
