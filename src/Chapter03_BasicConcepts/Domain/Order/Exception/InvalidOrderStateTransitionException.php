<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Exception;

final class InvalidOrderStateTransitionException extends \DomainException
{
    public static function cannotTransition(string $from, string $to): self
    {
        return new self(sprintf('Cannot transition from "%s" to "%s".', $from, $to));
    }

    /** Ne každé porušení je přechod – přidání položky mimo Draft také ne. */
    public static function notAllowedInState(string $operation, string $state): self
    {
        return new self(sprintf('Operation "%s" is not allowed in state "%s".', $operation, $state));
    }
}
