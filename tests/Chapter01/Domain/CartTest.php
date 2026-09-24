<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\Cart\Cart;
use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class CartTest extends TestCase
{
    public function test_can_add_product_to_cart(): void
    {
        $cart = Cart::empty();
        $cart->add($this->product(59_900), 2);

        self::assertSame(2, $cart->itemCount());
        self::assertTrue($cart->totalAmount()->equals(new Money(119_800, Currency::CZK)));
    }

    public function test_same_product_twice_adds_quantity(): void
    {
        $cart = Cart::empty();
        $product = $this->product(10_000);
        $cart->add($product, 1);
        $cart->add($product, 2);

        self::assertSame(3, $cart->itemCount());
        self::assertCount(1, $cart->summary());
        self::assertSame(30_000, $cart->summary()[0]['lineTotal']->amountInCents);
    }

    public function test_empty_cart_is_worth_zero(): void
    {
        self::assertTrue(Cart::empty()->totalAmount()->equals(Money::zero(Currency::CZK)));
    }

    public function test_cannot_add_product_with_zero_quantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Cart::empty()->add($this->product(59_900), 0);
    }

    private function product(int $cents): Product
    {
        return new Product(ProductId::generate(), 'Kniha', new Money($cents, Currency::CZK));
    }
}
