<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\Cart\Cart;
use App\Chapter01_WhatIsDDD\Domain\Product\Price;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\Product\ProductId;
use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase
{
    public function test_can_add_product_to_cart(): void
    {
        $cart = Cart::empty();
        $product = new Product(ProductId::generate(), 'Symfony kniha', new Price(59900, 'CZK'));
        $cart->add($product, 2);

        $this->assertSame(2, $cart->itemCount());
        $this->assertEquals(new Price(119800, 'CZK'), $cart->total());
    }

    public function test_cannot_add_product_with_zero_quantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $cart = Cart::empty();
        $cart->add(new Product(ProductId::generate(), 'Kniha', new Price(59900, 'CZK')), 0);
    }
}
