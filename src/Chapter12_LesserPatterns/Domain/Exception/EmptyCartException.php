<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class EmptyCartException extends DomainRuleViolation
{
    public static function cannotPlaceOrder(): self
    {
        return new self('Z prázdného košíku objednávka nevznikne.');
    }
}
