<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use PHPUnit\Framework\TestCase;

final class SharedKernelProductIdTest extends TestCase
{
    public function test_creation_with_valid_value(): void
    {
        $id = new ProductId('prod-123');

        $this->assertSame('prod-123', $id->value);
    }

    public function test_empty_value_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProductId('');
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $id1 = new ProductId('prod-42');
        $id2 = new ProductId('prod-42');

        $this->assertTrue($id1->equals($id2));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $id1 = new ProductId('prod-42');
        $id2 = new ProductId('prod-99');

        $this->assertFalse($id1->equals($id2));
    }
}
