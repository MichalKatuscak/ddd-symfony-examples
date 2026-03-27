<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\BoundedContext\CatalogProduct;
use App\Chapter01_WhatIsDDD\Domain\BoundedContext\OrderProduct;
use PHPUnit\Framework\TestCase;

final class BoundedContextTest extends TestCase
{
    public function test_same_product_id_can_exist_in_different_bounded_contexts(): void
    {
        $catalogProduct = new CatalogProduct(
            id: 'prod-42',
            name: 'Symfony kniha',
            description: 'Kniha o Symfony frameworku',
            stockQty: 10,
            weightKg: 0.5,
        );

        $orderProduct = new OrderProduct(
            productId: 'prod-42',
            unitPriceCents: 59900,
            currency: 'CZK',
            taxRate: 0.21,
        );

        $this->assertSame('prod-42', $catalogProduct->id);
        $this->assertSame('prod-42', $orderProduct->productId);
    }

    public function test_catalog_product_has_stock_qty_and_weight_kg(): void
    {
        $catalogProduct = new CatalogProduct(
            id: 'prod-42',
            name: 'Symfony kniha',
            description: 'Popis produktu',
            stockQty: 15,
            weightKg: 1.2,
        );

        $this->assertSame('prod-42', $catalogProduct->id);
        $this->assertSame('Symfony kniha', $catalogProduct->name);
        $this->assertSame(15, $catalogProduct->stockQty);
        $this->assertSame(1.2, $catalogProduct->weightKg);
    }

    public function test_order_product_has_unit_price_cents_currency_and_tax_rate(): void
    {
        $orderProduct = new OrderProduct(
            productId: 'prod-42',
            unitPriceCents: 59900,
            currency: 'CZK',
            taxRate: 0.21,
        );

        $this->assertSame('prod-42', $orderProduct->productId);
        $this->assertSame(59900, $orderProduct->unitPriceCents);
        $this->assertSame('CZK', $orderProduct->currency);
        $this->assertSame(0.21, $orderProduct->taxRate);
    }

    public function test_same_product_id_has_different_attributes_in_different_contexts(): void
    {
        $catalogProduct = new CatalogProduct(
            id: 'prod-42',
            name: 'Klávesnice',
            description: 'Mechanická klávesnice',
            stockQty: 5,
            weightKg: 0.8,
        );

        $orderProduct = new OrderProduct(
            productId: 'prod-42',
            unitPriceCents: 299900,
            currency: 'CZK',
            taxRate: 0.21,
        );

        // Same productId, but each context has its own representation
        $this->assertSame($catalogProduct->id, $orderProduct->productId);
        // CatalogProduct knows about stock and weight, OrderProduct does not
        $this->assertSame(5, $catalogProduct->stockQty);
        $this->assertSame(0.8, $catalogProduct->weightKg);
        // OrderProduct knows about price and tax, CatalogProduct does not
        $this->assertSame(299900, $orderProduct->unitPriceCents);
        $this->assertSame(0.21, $orderProduct->taxRate);
    }
}
