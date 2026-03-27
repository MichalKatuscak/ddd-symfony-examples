<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\BoundedContext;

final readonly class OrderProduct
{
    public function __construct(
        public readonly string $productId,
        public readonly int $unitPriceCents,
        public readonly string $currency,
        public readonly float $taxRate,
    ) {}
}
