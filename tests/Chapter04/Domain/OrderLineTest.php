<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\Domain;

use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\OrderLine;
use PHPUnit\Framework\TestCase;

final class OrderLineTest extends TestCase
{
    public function test_valid_construction(): void
    {
        $line = new OrderLine('Symfony kniha', 2, new Money(59900, 'CZK'));
        $this->assertSame('Symfony kniha', $line->productName);
        $this->assertSame(2, $line->quantity);
        $this->assertEquals(new Money(59900, 'CZK'), $line->unitPrice);
    }

    public function test_line_total_equals_unit_price_times_quantity(): void
    {
        $line = new OrderLine('Produkt', 3, new Money(10000, 'CZK'));
        $this->assertEquals(new Money(30000, 'CZK'), $line->lineTotal());
    }

    public function test_quantity_less_than_one_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrderLine('Produkt', 0, new Money(10000, 'CZK'));
    }

    public function test_negative_quantity_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrderLine('Produkt', -1, new Money(10000, 'CZK'));
    }

    public function test_empty_product_name_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new OrderLine('', 1, new Money(10000, 'CZK'));
    }

    public function test_to_array_returns_correct_shape(): void
    {
        $line = new OrderLine('Symfony kniha', 2, new Money(59900, 'CZK'));
        $expected = [
            'name' => 'Symfony kniha',
            'qty' => 2,
            'price' => 59900,
        ];
        $this->assertSame($expected, $line->toArray());
    }

    public function test_from_array_roundtrip(): void
    {
        $original = new OrderLine('DDD kniha', 1, new Money(79900, 'CZK'));
        $data = $original->toArray();
        $restored = OrderLine::fromArray($data);

        $this->assertSame($original->productName, $restored->productName);
        $this->assertSame($original->quantity, $restored->quantity);
        $this->assertSame($original->unitPrice->amount, $restored->unitPrice->amount);
        $this->assertSame($original->unitPrice->currency, $restored->unitPrice->currency);
    }

    public function test_from_array_currency_is_czk(): void
    {
        $line = OrderLine::fromArray(['name' => 'Test', 'qty' => 1, 'price' => 5000]);
        $this->assertSame('CZK', $line->unitPrice->currency);
    }
}
