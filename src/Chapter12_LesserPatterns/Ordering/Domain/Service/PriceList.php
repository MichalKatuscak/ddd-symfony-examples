<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Service;

use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Shared\Domain\Money;

/**
 * Port: odkud se berou ceny a zařazení zákazníka do cenové skupiny,
 * doménu nezajímá (ERP, databáze, konfigurace). Pravidlo, jak z nich
 * vznikne cena položky, drží PricingService.
 */
interface PriceList
{
    public function unitPriceOf(ProductId $productId): Money;

    /** Sleva cenové skupiny zákazníka v celých procentech (0 = bez slevy). */
    public function discountPercentFor(CustomerId $customerId): int;
}
