<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order;

use App\Shared\Domain\Money;

/**
 * Entita uvnitř agregátu. Zvenku ji nikdo neinstancuje ani nemění –
 * jediná cesta k ní vede přes Order::addItem().
 */
final class OrderItem
{
    public function __construct(
        public readonly ProductId $productId,
        public private(set) int $quantity,
        public readonly Money $unitPrice,
    ) {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Množství musí být kladné.');
        }
    }

    /** Invariant „jedna položka na produkt“: opakovaný nákup zvýší množství. */
    public function increaseQuantity(int $by): void
    {
        if ($by <= 0) {
            throw new \InvalidArgumentException('Přírůstek musí být kladný.');
        }

        $this->quantity += $by;
    }

    public function subtotal(): Money
    {
        return $this->unitPrice->multiply($this->quantity);
    }
}
