<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Application\EvaluatePromo;

final readonly class EvaluatePromoCommand
{
    public function __construct(
        public string $orderId,
        public int $freeShippingThresholdMinorUnits = 100_000,
    ) {}
}
