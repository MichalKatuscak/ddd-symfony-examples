<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Shared\Domain\Currency;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_constructs_with_valid_amount_and_currency(): void
    {
        $money = new Money(10000, Currency::CZK);
        $this->assertSame(10000, $money->amountInCents);
        $this->assertSame(Currency::CZK, $money->currency);
    }

    public function test_negative_amount_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Money(-1, Currency::CZK);
    }

    public function test_zero_amount_is_valid(): void
    {
        $money = new Money(0, Currency::CZK);
        $this->assertSame(0, $money->amountInCents);
    }

    public function test_add_returns_correct_sum(): void
    {
        $a = new Money(10000, Currency::CZK);
        $b = new Money(5000, Currency::CZK);
        $this->assertEquals(new Money(15000, Currency::CZK), $a->add($b));
    }

    public function test_add_different_currencies_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $a = new Money(10000, Currency::CZK);
        $b = new Money(5000, Currency::EUR);
        $a->add($b);
    }

    public function test_multiply_returns_correct_product(): void
    {
        $money = new Money(5000, Currency::CZK);
        $this->assertEquals(new Money(15000, Currency::CZK), $money->multiply(3));
    }

    public function test_formatted_returns_correct_string(): void
    {
        $money = new Money(59900, Currency::CZK);
        $this->assertSame('599.00 CZK', $money->formatted());
    }

    public function test_immutability_add_does_not_change_original(): void
    {
        $original = new Money(10000, Currency::CZK);
        $original->add(new Money(5000, Currency::CZK));
        $this->assertSame(10000, $original->amountInCents);
    }

    public function test_immutability_multiply_does_not_change_original(): void
    {
        $original = new Money(10000, Currency::CZK);
        $original->multiply(3);
        $this->assertSame(10000, $original->amountInCents);
    }
}
