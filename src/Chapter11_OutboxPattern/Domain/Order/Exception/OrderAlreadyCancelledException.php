<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Order\Exception;

use App\Shared\Domain\Exception\DomainRuleViolation;

final class OrderAlreadyCancelledException extends DomainRuleViolation
{
    public static function withId(string $orderId): self
    {
        return new self(sprintf('Objednávka „%s“ už je zrušená.', $orderId));
    }
}
