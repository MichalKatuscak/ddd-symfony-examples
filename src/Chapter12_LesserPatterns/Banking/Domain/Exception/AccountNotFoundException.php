<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Banking\Domain\Exception;

use App\Chapter12_LesserPatterns\Banking\Domain\AccountId;

final class AccountNotFoundException extends \DomainException
{
    public static function withId(AccountId $id): self
    {
        return new self(sprintf('Account "%s" not found.', $id->value));
    }
}
