<?php

declare(strict_types=1);

namespace App\Tests\Chapter01\Domain;

use App\Chapter01_WhatIsDDD\Domain\Product\Product;
use App\Chapter01_WhatIsDDD\Domain\SharedKernel\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function test_constructor_sets_id_name_and_price(): void
    {
        $id = ProductId::generate();
        $price = new Money(59_900, Currency::CZK);
        $product = new Product($id, 'Symfony kniha', $price);

        self::assertSame($id, $product->id);
        self::assertSame('Symfony kniha', $product->name());
        self::assertSame($price, $product->price());
    }
}
