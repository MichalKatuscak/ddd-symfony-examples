<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Specification;

use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\SharedKernel\Domain\Specification\CompositeSpecification;

/**
 * Doručovací adresa objednávky se nachází v členské zemi EU.
 * Seznam zemí je součástí pravidla – specifikace nepotřebuje
 * žádný vstup zvenčí.
 *
 * @extends CompositeSpecification<Order>
 */
final class InEUCountry extends CompositeSpecification
{
    private const array EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    public function isSatisfiedBy(mixed $candidate): bool
    {
        assert($candidate instanceof Order);

        // Nullsafe navíc proti knize: digitální objednávka adresu nemá,
        // takže do EU nic nedoručuje.
        return in_array(
            $candidate->shippingAddress?->countryCode,
            self::EU_COUNTRIES,
            true,
        );
    }
}
