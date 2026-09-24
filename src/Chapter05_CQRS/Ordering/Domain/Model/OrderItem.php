<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Model;

use App\Chapter05_CQRS\Ordering\Domain\ValueObject\ProductId;
use App\Shared\Domain\Money;

/**
 * Entita uvnitř agregátu; vzniká jen přes Order::addItem().
 *
 * Kniha jí dává náhradní int identitu a zpětnou referenci na Order kvůli
 * mapování ManyToOne. Write model ukázky běží bez Doctrine, takže obojí
 * odpadá.
 */
final class OrderItem
{
    public function __construct(
        public readonly ProductId $productId,
        public private(set) int $quantity,
        public readonly Money $unitPrice,
    ) {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    }

    public function increaseQuantity(int $by): void
    {
        if ($by < 1) {
            throw new \InvalidArgumentException('Quantity increment must be positive');
        }

        $this->quantity += $by;
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
