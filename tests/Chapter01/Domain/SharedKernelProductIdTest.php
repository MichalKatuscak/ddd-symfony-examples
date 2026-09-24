<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SharedKernelProductIdTest extends TestCase
{
    public function test_generate_produces_valid_unique_uuid(): void
    {
        $first = ProductId::generate();
        $second = ProductId::generate();

        self::assertTrue(Uuid::isValid($first->value));
        self::assertFalse($first->equals($second));
    }

    public function test_non_uuid_value_throws(): void
    {
        // Na formátu sdílené identity se musí shodnout oba kontexty.
        $this->expectException(\InvalidArgumentException::class);
        new ProductId('prod-42');
    }

    public function test_empty_value_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductId('');
    }

    public function test_equality_by_value(): void
    {
        $value = ProductId::generate()->value;

        self::assertTrue(ProductId::fromString($value)->equals(new ProductId($value)));
        self::assertSame($value, (string) ProductId::fromString($value));
    }
}
