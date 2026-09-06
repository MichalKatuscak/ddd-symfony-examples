<?php

declare(strict_types=1);

namespace App\Chapter09_Migration\Domain\Task\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class InvalidTaskStateTransitionException extends DomainRuleViolation
{
    public static function cannotStart(): self
    {
        return new self('Úkol je už rozpracovaný nebo hotový.');
    }

    public static function cannotComplete(): self
    {
        return new self('Dokončit lze jen rozpracovaný úkol.');
    }

    public static function cannotReassign(): self
    {
        return new self('Hotový úkol už nelze přiřadit jinam.');
    }
}
