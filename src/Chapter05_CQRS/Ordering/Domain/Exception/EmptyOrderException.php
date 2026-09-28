<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Exception;

final class EmptyOrderException extends \DomainException
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
