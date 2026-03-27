<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\ContextMap\CatalogProductTranslator;
use PHPUnit\Framework\TestCase;

final class CatalogProductTranslatorTest extends TestCase
{
    public function test_to_order_product_correctly_translates_catalog_product(): void
    {
        $catalogProduct = new CatalogProduct(
            id: 'prod-42',
            name: 'Symfony kniha',
            description: 'Kniha o DDD v Symfony',
            stockQty: 10,
            weightKg: 0.5,
        );

        $translator = new CatalogProductTranslator();
        $orderProduct = $translator->toOrderProduct($catalogProduct, 59900, 'CZK', 0.21);

        $this->assertSame('prod-42', $orderProduct->productId);
        $this->assertSame(59900, $orderProduct->unitPriceCents);
        $this->assertSame('CZK', $orderProduct->currency);
        $this->assertSame(0.21, $orderProduct->taxRate);
    }

    public function test_translated_order_product_uses_catalog_product_id(): void
    {
        $catalogProduct = new CatalogProduct(
            id: 'unique-product-id-999',
            name: 'Test produkt',
            description: 'Popis',
            stockQty: 1,
            weightKg: 0.1,
        );

        $translator = new CatalogProductTranslator();
        $orderProduct = $translator->toOrderProduct($catalogProduct, 10000, 'EUR', 0.19);

        $this->assertSame($catalogProduct->id, $orderProduct->productId);
    }
}
