<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class AccessDeniedDomainException extends DomainRuleViolation
{
}
