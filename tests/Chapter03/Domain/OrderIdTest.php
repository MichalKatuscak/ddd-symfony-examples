<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use PHPUnit\Framework\TestCase;

final class OrderIdTest extends TestCase
{
    public function test_constructs_with_valid_value(): void
    {
        $id = new OrderId('some-id-123');
        $this->assertSame('some-id-123', $id->value);
    }

    public function test_empty_value_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrderId('');
    }

    public function test_generate_returns_non_empty(): void
    {
        $id = OrderId::generate();
        $this->assertNotEmpty($id->value);
    }

    public function test_generate_returns_unique_values(): void
    {
        $id1 = OrderId::generate();
        $id2 = OrderId::generate();
        $this->assertNotSame($id1->value, $id2->value);
    }
}
