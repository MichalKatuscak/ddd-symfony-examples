<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\Product;

use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use App\Shared\Domain\Money;

/** Entita: produkt v nabídce e-shopu, identitu nese ProductId. */
final class Product
{
    public function __construct(
        public readonly ProductId $id,
        private readonly string $name,
        private readonly Money $price,
    ) {}

    public function name(): string { return $this->name; }
    public function price(): Money { return $this->price; }
}
