<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\BoundedContext;

final readonly class CatalogProduct
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $stockQty,
        public float $weightKg,
    ) {}
}
