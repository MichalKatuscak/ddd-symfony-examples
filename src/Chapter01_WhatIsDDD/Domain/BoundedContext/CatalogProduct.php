<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\BoundedContext;

use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;

/** Produkt v kontextu Katalog: co zboží je a zda je skladem. */
final readonly class CatalogProduct
{
    public function __construct(
        public ProductId $id,
        public string $name,
        public string $description,
        public int $stockQty,
        public float $weightKg,
    ) {}
}
