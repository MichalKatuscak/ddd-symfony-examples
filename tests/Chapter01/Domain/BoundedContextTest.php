<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\BoundedContext\OrderProduct;
use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class BoundedContextTest extends TestCase
{
    public function test_same_product_has_different_model_in_each_context(): void
    {
        $id = ProductId::generate();

        $catalogProduct = new CatalogProduct(
            id: $id,
            name: 'Klávesnice',
            description: 'Mechanická klávesnice',
            stockQty: 5,
            weightKg: 0.8,
        );

        $orderProduct = new OrderProduct(
            productId: $id,
            unitPrice: new Money(299_900, Currency::CZK),
            vatRatePercent: 21,
        );

        // Společná je jen identita, každý kontext si drží vlastní atributy.
        self::assertTrue($catalogProduct->id->equals($orderProduct->productId));
        self::assertSame(5, $catalogProduct->stockQty);
        self::assertSame(0.8, $catalogProduct->weightKg);
        self::assertSame(299_900, $orderProduct->unitPrice->amountInCents);
        self::assertSame(21, $orderProduct->vatRatePercent);
    }
}
