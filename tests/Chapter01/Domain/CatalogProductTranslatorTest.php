<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\ContextMap\CatalogProductTranslator;
use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class CatalogProductTranslatorTest extends TestCase
{
    public function test_translates_catalog_product_to_order_product(): void
    {
        $catalogProduct = new CatalogProduct(
            id: ProductId::generate(),
            name: 'Symfony kniha',
            description: 'Kniha o DDD v Symfony',
            stockQty: 10,
            weightKg: 0.5,
        );
        $price = new Money(59_900, Currency::CZK);

        $orderProduct = (new CatalogProductTranslator())->toOrderProduct($catalogProduct, $price, 21);

        self::assertTrue($orderProduct->productId->equals($catalogProduct->id));
        self::assertTrue($orderProduct->unitPrice->equals($price));
        self::assertSame(21, $orderProduct->vatRatePercent);
    }
}
