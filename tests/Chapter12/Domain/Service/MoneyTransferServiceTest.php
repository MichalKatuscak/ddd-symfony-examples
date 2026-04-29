<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Domain\Service;

use App\Chapter12_LesserPatterns\Domain\Order\Money;
use App\Chapter12_LesserPatterns\Domain\Service\Account;
use App\Chapter12_LesserPatterns\Domain\Service\InsufficientFundsException;
use App\Chapter12_LesserPatterns\Domain\Service\MoneyTransferService;
use PHPUnit\Framework\TestCase;

final class MoneyTransferServiceTest extends TestCase
{
    public function test_transfer_moves_money_between_two_accounts(): void
    {
        $from = new Account('acc-from', new Money(50_000));
        $to = new Account('acc-to', new Money(10_000));

        (new MoneyTransferService())->transfer($from, $to, new Money(20_000));

        $this->assertSame(30_000, $from->balance()->amount);
        $this->assertSame(30_000, $to->balance()->amount);
    }

    public function test_transfer_throws_when_source_account_lacks_funds(): void
    {
        $from = new Account('acc-from', new Money(10_000));
        $to = new Account('acc-to', new Money(0));

        $this->expectException(InsufficientFundsException::class);

        (new MoneyTransferService())->transfer($from, $to, new Money(50_000));
    }

    public function test_transfer_to_same_account_is_rejected(): void
    {
        $account = new Account('acc-1', new Money(10_000));

        $this->expectException(\DomainException::class);

        (new MoneyTransferService())->transfer($account, $account, new Money(1_000));
    }

    public function test_currency_mismatch_is_rejected(): void
    {
        $from = new Account('acc-from', new Money(10_000, 'CZK'));
        $to = new Account('acc-to', new Money(0, 'EUR'));

        $this->expectException(\DomainException::class);

        (new MoneyTransferService())->transfer($from, $to, new Money(1_000, 'CZK'));
    }
}
