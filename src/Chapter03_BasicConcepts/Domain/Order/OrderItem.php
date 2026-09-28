<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Order;

use App\Shared\Domain\Money;

// Záměrně zjednodušený neměnný záznam bez odkazu zpět na objednávku;
// identitu mu uvnitř agregátu stačí dát produkt. Plnou verzi
// s increaseQuantity() ukazuje Chapter02_AggregateDesign.
class OrderItem
{
    public function __construct(
        public readonly ProductId $productId,
        public readonly int $quantity,
        public readonly Money $unitPrice,
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive');
        }
    }
}
