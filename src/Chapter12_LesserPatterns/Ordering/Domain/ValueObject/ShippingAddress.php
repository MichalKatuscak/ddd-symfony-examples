<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject;

/**
 * Hodnotový objekt z kapitoly Návrh agregátu (tam jako embeddable).
 * Ukázka běží bez Doctrine, mapovací atributy proto vynechává.
 */
final readonly class ShippingAddress
{
    public function __construct(
        public string $street,
        public string $city,
        public string $postalCode,
        public string $countryCode,
    ) {
        if (strlen($countryCode) !== 2) {
            throw new \InvalidArgumentException('Country code must be ISO 3166-1 alpha-2');
        }
    }
}
