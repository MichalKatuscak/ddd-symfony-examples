<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class EmptyOrderException extends DomainRuleViolation
{
    public static function cannotConfirm(): self
    {
        return new self('Cannot confirm an order without items.');
    }

    public static function cannotBePlaced(): self
    {
        return new self('Order must contain at least one item.');
    }
}
