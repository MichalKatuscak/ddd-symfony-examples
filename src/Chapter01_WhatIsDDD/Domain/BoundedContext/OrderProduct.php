<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\BoundedContext;

use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use App\Shared\Domain\Money;

/** Produkt v kontextu Objednávky: za kolik a s jakou sazbou DPH se prodal. */
final readonly class OrderProduct
{
    public function __construct(
        public ProductId $productId,
        public Money $unitPrice,
        // Sazby jsou celá procenta, stejně jako u Money::percentage().
        public int $vatRatePercent,
    ) {}
}
