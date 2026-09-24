<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Domain\Order\Exception;

use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Shared\Domain\Exception\DomainRuleViolation;

/** Právo je v pořádku, jen uplynula lhůta. Odtud 409, ne 403. */
final class CancellationWindowExpiredException extends DomainRuleViolation
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly \DateTimeImmutable $placedAt,
        public readonly \DateTimeImmutable $attemptedAt,
    ) {
        parent::__construct(sprintf(
            'Objednávku „%s“ potvrzenou %s už nelze stornovat (pokus %s).',
            $orderId->value,
            $placedAt->format('Y-m-d H:i'),
            $attemptedAt->format('Y-m-d H:i'),
        ));
    }
}
