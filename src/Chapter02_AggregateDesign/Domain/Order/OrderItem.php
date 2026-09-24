<?php

declare(strict_types=1);

namespace App\Chapter02_AggregateDesign\Domain\Order;

use App\Shared\Domain\Money;

/**
 * Entita uvnitř agregátu. Zvenku ji nikdo neinstancuje ani nemění –
 * jediná cesta k ní vede přes Order::addItem().
 *
 * Kniha (07.08) jí dává náhradní int identitu a zpětnou referenci na
 * Order kvůli mapování ManyToOne. Ukázka běží bez Doctrine, takže obojí
 * odpadá a položku uvnitř agregátu identifikuje produkt.
 */
final class OrderItem
{
    public function __construct(
        public readonly ProductId $productId,
        // private(set): zvenčí čitelné, měnit smí jen položka sama.
        // readonly by nešlo – increaseQuantity() hodnotu mění.
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
