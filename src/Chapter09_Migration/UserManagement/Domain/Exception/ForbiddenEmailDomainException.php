<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\UserManagement\Domain\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

/**
 * Kniha výjimku jen používá (Email::fromUserInput()), výpis nemá.
 * Stavba odpovídá ostatním výjimkám kontextu: pojmenovaná továrna
 * nese formulaci chyby.
 */
final class ForbiddenEmailDomainException extends DomainRuleViolation
{
    public static function forDomain(string $domain): self
    {
        return new self(sprintf('Registration from domain "%s" is not allowed.', $domain));
    }
}
