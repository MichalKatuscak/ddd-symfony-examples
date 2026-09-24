<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Model;

use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Shared\Domain\Money;

/**
 * Digitální obsah (licence, e-kniha): kupuje se jednou, nemá množství
 * ani doručovací adresu.
 */
final readonly class DigitalItem
{
    public function __construct(
        public ProductId $productId,
        public Money $price,
    ) {}

    public function toOrderItem(): OrderItem
    {
        return new OrderItem($this->productId, 1, $this->price);
    }
}
