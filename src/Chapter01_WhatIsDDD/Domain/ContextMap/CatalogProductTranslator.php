<?php

declare(strict_types=1);

namespace App\Chapter01_WhatIsDDD\Domain\ContextMap;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\BoundedContext\OrderProduct;
use App\Shared\Domain\Money;

/**
 * Anti-Corruption Layer: kontext Objednávky nepřebírá model katalogu,
 * jen z něj přeloží to, co sám potřebuje. Změna v CatalogProduct se
 * zastaví zde.
 */
final class CatalogProductTranslator
{
    public function toOrderProduct(CatalogProduct $catalog, Money $price, int $vatRatePercent): OrderProduct
    {
        return new OrderProduct(
            productId: $catalog->id,
            unitPrice: $price,
            vatRatePercent: $vatRatePercent,
        );
    }
}
