<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

use App\Chapter12_LesserPatterns\Domain\Specification\CompositeSpecification;

/**
 * Konkrétní doménová specifikace — objednávka má nárok na dopravu zdarma,
 * pokud její celková cena dosáhne na zadaný práh.
 *
 * @extends CompositeSpecification<Order>
 */
final class OrderEligibleForFreeShipping extends CompositeSpecification
{
    public function __construct(
        private readonly Money $threshold,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Order) {
            return false;
        }

        return $candidate->total()->isGreaterThanOrEqual($this->threshold);
    }
}
