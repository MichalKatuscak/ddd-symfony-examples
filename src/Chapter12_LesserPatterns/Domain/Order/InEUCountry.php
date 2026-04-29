<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

use App\Chapter12_LesserPatterns\Domain\Specification\CompositeSpecification;

/**
 * Specifikace — objednávka jde do členské země EU.
 *
 * @extends CompositeSpecification<Order>
 */
final class InEUCountry extends CompositeSpecification
{
    /** @var list<string> */
    private const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Order) {
            return false;
        }

        return in_array($candidate->shippingCountry(), self::EU_COUNTRIES, true);
    }
}
