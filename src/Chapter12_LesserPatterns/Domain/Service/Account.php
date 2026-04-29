<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Service;

use App\Chapter12_LesserPatterns\Domain\Order\Money;

/**
 * Bankovní účet — agregát s interními invarianty kolem zůstatku.
 *
 * Účet zná pouze sám sebe — neumí "převést na jiný účet". Tato koordinační
 * logika patří do Domain Service (MoneyTransferService), protože by jinak
 * agregát musel znát detaily druhého agregátu.
 */
final class Account
{
    private Money $balance;

    public function __construct(
        private readonly string $id,
        Money $openingBalance,
    ) {
        if ($id === '') {
            throw new \InvalidArgumentException('Account id cannot be empty');
        }
        $this->balance = $openingBalance;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function withdraw(Money $amount): void
    {
        if (! $this->balance->isGreaterThanOrEqual($amount)) {
            throw new InsufficientFundsException(
                sprintf(
                    'Account %s has %s, cannot withdraw %s',
                    $this->id,
                    $this->balance->formatted(),
                    $amount->formatted(),
                ),
            );
        }

        $this->balance = $this->balance->subtract($amount);
    }

    public function deposit(Money $amount): void
    {
        $this->balance = $this->balance->add($amount);
    }
}
