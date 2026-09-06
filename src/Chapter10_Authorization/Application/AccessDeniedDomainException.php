<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Application;

use App\Shared\Domain\Exception\DomainRuleViolation;

/** Aktér na operaci nemá právo. Aplikační vrstva to překládá na 403. */
final class AccessDeniedDomainException extends DomainRuleViolation
{
}
