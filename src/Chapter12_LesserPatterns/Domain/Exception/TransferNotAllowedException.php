<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class TransferNotAllowedException extends DomainRuleViolation
{
    public static function sameAccount(): self
    {
        return new self('Převod na tentýž účet nedává smysl.');
    }

    public static function insufficientFunds(string $accountId): self
    {
        return new self(sprintf('Na účtu „%s“ není dost prostředků.', $accountId));
    }
}
