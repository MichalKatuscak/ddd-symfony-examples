<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Banking;

use App\Chapter12_LesserPatterns\Banking\Domain\Account;
use App\Chapter12_LesserPatterns\Banking\Domain\AccountId;
use App\Chapter12_LesserPatterns\Banking\Domain\Exception\InsufficientFunds;
use App\Chapter12_LesserPatterns\Banking\Domain\Service\MoneyTransferService;
use App\Chapter12_LesserPatterns\Banking\Domain\TransferReference;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTransferServiceTest extends TestCase
{
    private MoneyTransferService $service;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->service = new MoneyTransferService();
        $this->now = new \DateTimeImmutable('2026-03-01 10:00:00');
    }

    private static function account(int $cents, Currency $currency = Currency::CZK): Account
    {
        return new Account(AccountId::generate(), new Money($cents, $currency));
    }

    public function test_moves_money_and_keeps_the_sum(): void
    {
        $from = self::account(50_000);
        $to = self::account(10_000);
        $reference = TransferReference::generate();

        $this->service->transfer($from, $to, new Money(20_000, Currency::CZK), $reference, $this->now);

        self::assertSame(30_000, $from->balance()->amountInCents);
        self::assertSame(30_000, $to->balance()->amountInCents);
        // Oba pohyby nesou tutéž referenci – převod jde dohledat jako pár.
        self::assertSame($reference->value, $from->movements()[0]['reference']);
        self::assertSame($reference->value, $to->movements()[0]['reference']);
    }

    public function test_insufficient_funds_leaves_both_accounts_untouched(): void
    {
        $from = self::account(10_000);
        $to = self::account(0);

        try {
            $this->service->transfer($from, $to, new Money(20_000, Currency::CZK), TransferReference::generate(), $this->now);
            self::fail('Převod bez krytí měl selhat.');
        } catch (InsufficientFunds) {
        }

        self::assertSame(10_000, $from->balance()->amountInCents);
        self::assertSame(0, $to->balance()->amountInCents);
    }

    public function test_cross_currency_transfer_is_refused(): void
    {
        $from = self::account(50_000);
        $to = self::account(10_000, Currency::EUR);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Currency mismatch');

        $this->service->transfer($from, $to, new Money(1_000, Currency::CZK), TransferReference::generate(), $this->now);
    }

    public function test_account_guards_its_own_balance(): void
    {
        $this->expectException(InsufficientFunds::class);

        self::account(100)->withdraw(new Money(101, Currency::CZK), TransferReference::generate(), $this->now);
    }
}
