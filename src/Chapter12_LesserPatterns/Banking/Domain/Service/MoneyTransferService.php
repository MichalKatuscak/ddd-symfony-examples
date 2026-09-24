<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Domain\Service;

use App\Chapter12_LesserPatterns\Banking\Domain\Account;
use App\Chapter12_LesserPatterns\Banking\Domain\Exception\InsufficientFunds;
use App\Chapter12_LesserPatterns\Banking\Domain\TransferReference;
use App\Shared\Domain\Money;

/**
 * Domain Service – převod peněz mezi dvěma účty.
 *
 * Operace nepatří do žádného z účtů, protože jeden z nich nesmí znát
 * druhý: agregáty jsou autonomní. Jde o doménovou logiku (validace
 * dostupnosti prostředků, kontrola limitu), nikoliv o aplikační koordinaci.
 *
 * Bezstavová – mezi voláními nic nedrží, mění jen agregáty, které dostane.
 */
final class MoneyTransferService
{
    public function transfer(
        Account $from,
        Account $to,
        Money $amount,
        TransferReference $reference,
        \DateTimeImmutable $when,
    ): void {
        if (!$from->canWithdraw($amount, $when)) {
            throw InsufficientFunds::onAccount($from->id(), $amount);
        }

        if ($from->currency() !== $to->currency()) {
            // Holá \DomainException je zkratka; v projektu pojmenovaná výjimka.
            throw new \DomainException(
                'Currency mismatch – use FxTransferService for cross-currency transfers.',
            );
        }

        $from->withdraw($amount, $reference, $when);
        $to->deposit($amount, $reference, $when);
    }
}
