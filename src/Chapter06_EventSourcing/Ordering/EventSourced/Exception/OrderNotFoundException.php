<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Ordering\EventSourced\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class OrderNotFoundException extends DomainRuleViolation
{
    public static function withId(string $orderId): self
    {
        return new self(sprintf('Objednávka „%s“ neexistuje.', $orderId));
    }
}
