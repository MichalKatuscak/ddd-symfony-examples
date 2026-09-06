<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\Domain;

use App\Shared\Domain\Currency;
use App\Chapter04_Implementation\Domain\Order\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_add_with_same_currency(): void
    {
        $a = new Money(10000, Currency::CZK);
        $b = new Money(5000, Currency::CZK);
        $this->assertEquals(new Money(15000, Currency::CZK), $a->add($b));
    }

    public function test_add_with_different_currencies_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $a = new Money(10000, Currency::CZK);
        $b = new Money(5000, Currency::EUR);
        $a->add($b);
    }

    public function test_percentage_positive(): void
    {
        $money = new Money(10000, Currency::CZK);
        $result = $money->percentage(10);
        $this->assertEquals(new Money(1000, Currency::CZK), $result);
    }

    public function test_percentage_negative(): void
    {
        $money = new Money(10000, Currency::CZK);
        $result = $money->percentage(-10);
        $this->assertEquals(new Money(-1000, Currency::CZK), $result);
    }

    public function test_percentage_used_for_discount(): void
    {
        $price = new Money(100000, Currency::CZK);
        $discounted = $price->add($price->percentage(-10));
        $this->assertEquals(new Money(90000, Currency::CZK), $discounted);
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

    public function test_formatted_with_cents(): void
    {
        $money = new Money(10050, Currency::CZK);
        $this->assertSame('100.50 CZK', $money->formatted());
    }
}
