<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Specification;

use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\SharedKernel\Domain\Specification\CompositeSpecification;

/**
 * Zákazník není uveden na doménovém blacklistu (např. fraud detection).
 *
 * @extends CompositeSpecification<Order>
 */
final class NotInBlacklist extends CompositeSpecification
{
    /** @param list<CustomerId> $blacklist */
    public function __construct(private readonly array $blacklist) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof Order);

        foreach ($this->blacklist as $blocked) {
            if ($blocked->equals($candidate->customerId)) {
                return false;
            }
        }

        return true;
    }
}
