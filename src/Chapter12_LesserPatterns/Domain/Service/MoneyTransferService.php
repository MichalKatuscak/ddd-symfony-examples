<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Service;

use App\Chapter12_LesserPatterns\Domain\Exception\TransferNotAllowedException;
use App\Chapter12_LesserPatterns\Domain\Exception\CurrencyMismatchException;
use App\Chapter12_LesserPatterns\Domain\Order\Money;

/**
 * Domain Service — převod peněz mezi dvěma účty.
 *
 * Operace nepatří do žádného z účtů: jeden by musel znát druhý, čímž by
 * se porušila autonomie agregátů. Doménová logika (kontrola dostupných
 * prostředků, párování měn) bez přirozeného vlastníka.
 *
 * Stateless — bez instance state, bez perzistenčních volání.
 * Mutace na agregátech proběhnou, ale uložení provádí až Application
 * Service (command handler).
 */
final class MoneyTransferService
{
    public function transfer(Account $from, Account $to, Money $amount): void
    {
        if ($from->id() === $to->id()) {
            throw TransferNotAllowedException::sameAccount();
        }

        if ($from->balance()->currency !== $to->balance()->currency) {
            throw CurrencyMismatchException::betweenAccounts();
        }

        // Domain Service koordinuje: oba agregáty mutuje, ale ani jeden nemá přímou vazbu na druhý.
        $from->withdraw($amount);
        $to->deposit($amount);
    }
}
