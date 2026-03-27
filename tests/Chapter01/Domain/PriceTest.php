<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use PHPUnit\Framework\TestCase;

final class PriceTest extends TestCase
{
    public function test_creation_with_valid_amount_and_currency(): void
    {
        $price = new Price(59900, 'CZK');

        $this->assertSame(59900, $price->amount);
        $this->assertSame('CZK', $price->currency);
    }

    public function test_negative_amount_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Price(-1, 'CZK');
    }

    public function test_zero_amount_is_valid(): void
    {
        $price = new Price(0, 'CZK');

        $this->assertSame(0, $price->amount);
    }

    public function test_multiply_returns_new_price_with_correct_amount(): void
    {
        $price = new Price(59900, 'CZK');
        $result = $price->multiply(3);

        $this->assertSame(179700, $result->amount);
        $this->assertSame('CZK', $result->currency);
    }

    public function test_multiply_does_not_change_original(): void
    {
        $price = new Price(59900, 'CZK');
        $price->multiply(3);

        $this->assertSame(59900, $price->amount);
    }

    public function test_add_returns_sum_of_two_prices_in_same_currency(): void
    {
        $price1 = new Price(59900, 'CZK');
        $price2 = new Price(10000, 'CZK');
        $result = $price1->add($price2);

        $this->assertSame(69900, $result->amount);
        $this->assertSame('CZK', $result->currency);
    }

    public function test_add_with_different_currencies_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $price1 = new Price(59900, 'CZK');
        $price2 = new Price(10000, 'EUR');
        $price1->add($price2);
    }

    public function test_formatted_returns_correct_string(): void
    {
        $price = new Price(59900, 'CZK');

        $this->assertSame('599.00 CZK', $price->formatted());
    }

    public function test_immutability_multiply_does_not_change_original(): void
    {
        $original = new Price(10000, 'CZK');
        $multiplied = $original->multiply(5);

        $this->assertSame(10000, $original->amount);
        $this->assertSame(50000, $multiplied->amount);
        $this->assertNotSame($original, $multiplied);
    }
}
