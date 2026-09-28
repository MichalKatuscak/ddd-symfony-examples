<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Domain\Order\Exception;

use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Shared\Domain\Exception\DomainRuleViolation;

final class OrderNotFoundException extends DomainRuleViolation
{
    public static function withId(OrderId $id): self
    {
        return new self(sprintf('Order "%s" not found.', $id->value));
    }
}
