<?php

namespace App\Chapter01_WhatIsDDD\Domain\BoundedContext;

final readonly class CatalogProduct
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly int $stockQty,
        public readonly float $weightKg,
    ) {}
}
