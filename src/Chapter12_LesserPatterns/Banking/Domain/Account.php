<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Domain;

use App\Chapter12_LesserPatterns\Banking\Domain\Exception\InsufficientFunds;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

/**
 * Bankovní účet – agregát, který hlídá vlastní zůstatek. O jiném účtu
 * neví nic; převod mezi dvěma účty proto koordinuje MoneyTransferService.
 *
 * Kniha třídu nerozepisuje, ukázka jí dává jen metody, které služba volá.
 */
final class Account
{
    /** @var list<array{reference: string, amountInCents: int, at: \DateTimeImmutable}> */
    private array $movements = [];

    public function __construct(
        private readonly AccountId $id,
        private Money $balance,
    ) {}

    public function id(): AccountId
    {
        return $this->id;
    }

    public function currency(): Currency
    {
        return $this->balance->currency;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    /**
     * Čas vstupuje do pravidel typu denní limit výběru. Ukázka limit nemá,
     * rozhoduje jen zůstatek v měně účtu.
     */
    public function canWithdraw(Money $amount, \DateTimeImmutable $when): bool
    {
        return $amount->currency === $this->currency()
            && $amount->amountInCents <= $this->balance->amountInCents;
    }

    public function withdraw(Money $amount, TransferReference $reference, \DateTimeImmutable $when): void
    {
        // Invariant hlídá účet i sám, ne jen služba, která ho volá.
        if (!$this->canWithdraw($amount, $when)) {
            throw InsufficientFunds::onAccount($this->id, $amount);
        }

        $this->balance = $this->balance->subtract($amount);
        $this->movements[] = ['reference' => $reference->value, 'amountInCents' => -$amount->amountInCents, 'at' => $when];
    }

    public function deposit(Money $amount, TransferReference $reference, \DateTimeImmutable $when): void
    {
        $this->balance = $this->balance->add($amount);
        $this->movements[] = ['reference' => $reference->value, 'amountInCents' => $amount->amountInCents, 'at' => $when];
    }

    /** @return list<array{reference: string, amountInCents: int, at: \DateTimeImmutable}> */
    public function movements(): array
    {
        return $this->movements;
    }
}
