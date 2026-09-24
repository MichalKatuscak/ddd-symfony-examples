<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Domain\Exception;

use App\Chapter09_Migration\UserManagement\Domain\ValueObject\Email;
use App\Shared\Domain\Exception\DomainRuleViolation;

final class DuplicateEmailException extends DomainRuleViolation
{
    public static function with(Email $email, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Uživatel s e-mailem "%s" již existuje.', $email->value),
            previous: $previous,
        );
    }
}
