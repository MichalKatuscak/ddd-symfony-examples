<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class EmptyOrderException extends DomainRuleViolation
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
