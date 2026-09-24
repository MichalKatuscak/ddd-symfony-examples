<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Exception;

final class EmptyOrderException extends \DomainException
{
    public static function cannotConfirm(): self
    {
        return new self('Objednávku bez položek nelze potvrdit.');
    }

    public static function cannotBePlaced(): self
    {
        return new self('Objednávka musí mít alespoň jednu položku.');
    }
}
