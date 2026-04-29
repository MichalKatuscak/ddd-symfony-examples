<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

use App\Chapter12_LesserPatterns\Domain\Specification\CompositeSpecification;

/**
 * Specifikace — objednávka nesměřuje do země na sankčním seznamu.
 *
 * @extends CompositeSpecification<Order>
 */
final class NotInBlacklist extends CompositeSpecification
{
    /** @param list<string> $blacklistedCountries */
    public function __construct(
        private readonly array $blacklistedCountries,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Order) {
            return false;
        }

        $normalized = array_map(strtoupper(...), $this->blacklistedCountries);

        return ! in_array($candidate->shippingCountry(), $normalized, true);
    }
}
