<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Exception;

use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Shared\Domain\Exception\DomainRuleViolation;

final class OrderNotFoundException extends DomainRuleViolation
{
    public static function withId(OrderId $id): self
    {
        return new self(sprintf('Order "%s" not found.', $id->value));
    }
}
