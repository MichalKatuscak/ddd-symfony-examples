<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\Product\ProductId;
use PHPUnit\Framework\TestCase;

final class ProductIdTest extends TestCase
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

    public function test_generate_produces_non_empty_id(): void
    {
        $id = ProductId::generate();

        $this->assertNotEmpty($id->value);
    }

    public function test_generate_produces_unique_ids(): void
    {
        $id1 = ProductId::generate();
        $id2 = ProductId::generate();

        $this->assertNotSame($id1->value, $id2->value);
    }
}
