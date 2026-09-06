<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Domain\Order\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class InvalidOrderStateTransitionException extends DomainRuleViolation
{
    public static function cannotTransition(string $from, string $to): self
    {
        return new self(sprintf('Nelze přejít ze stavu „%s“ do stavu „%s“.', $from, $to));
    }
}
