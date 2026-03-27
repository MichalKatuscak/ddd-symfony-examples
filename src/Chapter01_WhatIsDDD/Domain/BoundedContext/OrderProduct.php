<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\BoundedContext;

final readonly class OrderProduct
{
    public function __construct(
        public string $productId,
        public int $unitPriceCents,
        public string $currency,
        public float $taxRate,
    ) {}
}
