<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Application\EvaluatePromo;

final readonly class EvaluatePromoResult
{
    public function __construct(
        public bool $eligible,
        public bool $aboveFreeShippingThreshold,
        public bool $shipsToEU,
        public bool $notInBlacklist,
        public string $orderTotalFormatted,
        public string $shippingCountry,
    ) {}
}
