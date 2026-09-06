<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class CustomerNotFoundException extends DomainRuleViolation
{
    public static function withId(string $customerId): self
    {
        return new self(sprintf('Zákazník „%s“ neexistuje.', $customerId));
    }
}
