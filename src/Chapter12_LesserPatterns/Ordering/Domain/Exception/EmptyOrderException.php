<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Exception;

final class EmptyOrderException extends \DomainException
{
    public static function cannotBePlaced(): self
    {
        return new self('Objednávka musí mít alespoň jednu položku.');
    }
}
