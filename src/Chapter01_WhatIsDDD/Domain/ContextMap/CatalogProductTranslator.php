<?php
declare(strict_types=1);
namespace App\Chapter01_WhatIsDDD\Domain\ContextMap;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\BoundedContext\OrderProduct;

final class CatalogProductTranslator
{
    public function toOrderProduct(CatalogProduct $catalog, int $priceCents, string $currency, float $taxRate): OrderProduct
    {
        return new OrderProduct(
            productId: $catalog->id,
            unitPriceCents: $priceCents,
            currency: $currency,
            taxRate: $taxRate,
        );
    }
}
