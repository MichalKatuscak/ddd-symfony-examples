<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\UserManagement\Domain\Exception;

use App\Chapter04_Implementation\UserManagement\Domain\ValueObject\Email;

final class DuplicateEmailException extends \DomainException
{
    public static function with(Email $email, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf('Uživatel s e-mailem "%s" již existuje.', $email->value),
            previous: $previous,
        );
    }
}
