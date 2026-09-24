<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Specification;

use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\SharedKernel\Domain\Specification\CompositeSpecification;
use App\Shared\Domain\Money;

/**
 * Objednávka má nárok na dopravu zdarma, pokud její celková hodnota
 * dosahuje nebo přesahuje stanovený limit.
 *
 * @extends CompositeSpecification<Order>
 */
final class EligibleForFreeShipping extends CompositeSpecification
{
    public function __construct(private readonly Money $threshold) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof Order);

        $total = $candidate->totalAmount();

        return $total->currency === $this->threshold->currency
            && $total->amountInCents >= $this->threshold->amountInCents;
    }
}
