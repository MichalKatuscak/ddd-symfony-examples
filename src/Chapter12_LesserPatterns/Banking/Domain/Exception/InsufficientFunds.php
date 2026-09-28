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
            'Account "%s" has insufficient funds to withdraw %d %s (in minor units).',
            $accountId->value,
            $amount->amountInCents,
            $amount->currency->value,
        ));
    }
}
