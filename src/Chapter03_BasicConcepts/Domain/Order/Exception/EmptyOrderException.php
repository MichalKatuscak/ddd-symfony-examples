<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Exception;

/**
 * Pojmenovaná doménová výjimka. Volající se rozhoduje podle typu,
 * ne podle textu zprávy.
 */
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
