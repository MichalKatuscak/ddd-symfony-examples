<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class CurrencyMismatchException extends DomainRuleViolation
{
    public static function betweenAccounts(): self
    {
        return new self('Převod mezi měnami řeší FxTransferService, ne tenhle.');
    }

    public static function cannotSubtract(): self
    {
        return new self('Odečtením by vznikla záporná částka.');
    }
}
