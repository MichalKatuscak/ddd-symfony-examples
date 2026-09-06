<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order\Exception;

final class InvalidOrderStateTransitionException extends \DomainException
{
    public static function cannotTransition(string $from, string $to): self
    {
        return new self(sprintf('Nelze přejít ze stavu „%s“ do stavu „%s“.', $from, $to));
    }

    /** Ne každé porušení je přechod – přidání položky mimo Draft taky ne. */
    public static function notAllowedInState(string $operation, string $state): self
    {
        return new self(sprintf('Operaci „%s“ nelze provést ve stavu „%s“.', $operation, $state));
    }
}
