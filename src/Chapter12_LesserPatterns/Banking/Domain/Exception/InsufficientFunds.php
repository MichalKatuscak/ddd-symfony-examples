<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Domain\Exception;

use App\Chapter12_LesserPatterns\Banking\Domain\AccountId;
use App\Shared\Domain\Money;

final class InsufficientFunds extends \DomainException
{
    public static function onAccount(AccountId $accountId, Money $amount): self
    {
        return new self(sprintf(
            'Na účtu „%s“ nejsou prostředky na výběr %d %s (v haléřích).',
            $accountId->value,
            $amount->amountInCents,
            $amount->currency->value,
        ));
    }
}
