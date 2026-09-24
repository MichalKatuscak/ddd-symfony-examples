<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Model;

use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Shared\Domain\Money;

/**
 * Položka objednávky. Na rozdíl od kanonické položky z kapitoly Návrh
 * agregátu ji varianta s továrnami nemění: položky vznikají najednou
 * při placePhysical() / placeDigital() a zůstávají.
 */
final readonly class OrderItem
{
    public function __construct(
        public ProductId $productId,
        public int $quantity,
        public Money $unitPrice,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
