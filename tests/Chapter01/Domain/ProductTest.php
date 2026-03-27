<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\Product\ProductId;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function test_constructor_sets_id_name_and_price(): void
    {
        $id = new ProductId('prod-001');
        $price = new Price(59900, 'CZK');
        $product = new Product($id, 'Symfony kniha', $price);

        $this->assertSame($id, $product->id());
        $this->assertSame('Symfony kniha', $product->name());
        $this->assertSame($price, $product->price());
    }

    public function test_getters_return_correct_values(): void
    {
        $id = new ProductId('prod-42');
        $price = new Price(29900, 'EUR');
        $product = new Product($id, 'DDD kniha', $price);

        $this->assertSame('prod-42', $product->id()->value);
        $this->assertSame('DDD kniha', $product->name());
        $this->assertSame(29900, $product->price()->amount);
        $this->assertSame('EUR', $product->price()->currency);
    }
}
