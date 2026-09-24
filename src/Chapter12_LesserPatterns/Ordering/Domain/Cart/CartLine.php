<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Cart;

use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;

/** Řádek košíku: co a kolik. Cenu mu přidělí až PricingService. */
final readonly class CartLine
{
    public function __construct(
        public ProductId $productId,
        public int $quantity,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    }
}
