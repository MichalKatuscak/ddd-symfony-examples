<?php

declare(strict_types=1);

namespace App\Tests\Shared;

use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_negative_amount_is_a_format_error(): void
    {
        // Porušení formátu hodnoty hlásí konstruktor výjimkou
        // \InvalidArgumentException (06.04, Validace: kde jaká výjimka).
        $this->expectException(\InvalidArgumentException::class);
        new Money(-1, Currency::CZK);
    }

    public function test_zero_has_given_currency(): void
    {
        $zero = Money::zero(Currency::EUR);

        self::assertSame(0, $zero->amountInCents);
        self::assertSame(Currency::EUR, $zero->currency);
    }

    public function test_add_and_subtract_keep_currency(): void
    {
        $a = new Money(10_000, Currency::CZK);
        $b = new Money(2_500, Currency::CZK);

        self::assertTrue($a->add($b)->equals(new Money(12_500, Currency::CZK)));
        self::assertTrue($a->subtract($b)->equals(new Money(7_500, Currency::CZK)));
    }

    public function test_adding_different_currencies_is_a_domain_rule(): void
    {
        // Sčítání dvou měn je doménové pravidlo, ne chyba formátu.
        $this->expectException(\DomainException::class);
        (new Money(100, Currency::CZK))->add(new Money(100, Currency::EUR));
    }

    public function test_subtracting_different_currencies_is_a_domain_rule(): void
    {
        $this->expectException(\DomainException::class);
        (new Money(100, Currency::CZK))->subtract(new Money(100, Currency::EUR));
    }

    public function test_subtracting_below_zero_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Money(100, Currency::CZK))->subtract(new Money(101, Currency::CZK));
    }

    public function test_multiply(): void
    {
        self::assertSame(15_000, (new Money(5_000, Currency::CZK))->multiply(3)->amountInCents);
    }

    public function test_percentage_rounds_up(): void
    {
        // 21 % z 99,99 Kč = 20,9979 Kč → 21,00 Kč
        self::assertSame(2_100, (new Money(9_999, Currency::CZK))->percentage(21)->amountInCents);
        self::assertSame(2_100, (new Money(10_000, Currency::CZK))->percentage(21)->amountInCents);
    }

    public function test_operations_do_not_change_original(): void
    {
        $original = new Money(10_000, Currency::CZK);
        $original->add(new Money(5_000, Currency::CZK));
        $original->multiply(3);

        self::assertSame(10_000, $original->amountInCents);
    }

    public function test_equality_by_value(): void
    {
        $a = new Money(100, Currency::CZK);

        self::assertTrue($a->equals(new Money(100, Currency::CZK)));
        self::assertFalse($a->equals(new Money(100, Currency::EUR)));
        self::assertFalse($a->equals(new Money(101, Currency::CZK)));
    }
}
