<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

final class EmptyOrderException extends \DomainException
{
    public static function cannotBePlaced(): self
    {
        return new self('Order must contain at least one item.');
    }
}
