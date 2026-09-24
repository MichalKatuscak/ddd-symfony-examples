<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Exception;

final class InvalidOrderStateTransitionException extends \DomainException
{
    public static function cannotTransition(string $from, string $to): self
    {
        return new self(sprintf(
            'Nelze přejít ze stavu „%s“ do stavu „%s“.',
            $from,
            $to,
        ));
    }

    public static function notAllowedInState(string $operation, string $state): self
    {
        return new self(sprintf(
            'Operaci „%s“ nelze provést ve stavu „%s“.',
            $operation,
            $state,
        ));
    }
}
