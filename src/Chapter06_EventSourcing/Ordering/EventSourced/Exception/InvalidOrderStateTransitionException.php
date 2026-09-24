<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Ordering\EventSourced\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class InvalidOrderStateTransitionException extends DomainRuleViolation
{
}
