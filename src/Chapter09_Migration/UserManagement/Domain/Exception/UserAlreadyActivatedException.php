<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Domain\Exception;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\UserId;
use App\Shared\Domain\Exception\DomainRuleViolation;

final class UserAlreadyActivatedException extends DomainRuleViolation
{
    public static function forUser(UserId $id): self
    {
        return new self(sprintf('User "%s" is already activated.', $id->value));
    }
}
