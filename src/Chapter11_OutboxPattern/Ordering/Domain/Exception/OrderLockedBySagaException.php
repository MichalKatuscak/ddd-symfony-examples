<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Exception;

use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;
use App\Shared\Domain\Exception\DomainRuleViolation;

final class OrderLockedBySagaException extends DomainRuleViolation
{
    public function __construct(public readonly OrderId $orderId)
    {
        parent::__construct(sprintf(
            'Order "%s" is locked by a running process.',
            $orderId->value,
        ));
    }
}
