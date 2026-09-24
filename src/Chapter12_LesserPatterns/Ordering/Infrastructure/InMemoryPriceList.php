<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Infrastructure;

use App\Chapter12_LesserPatterns\Ordering\Domain\Service\PriceList;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/** Ceník ukázky. V aplikaci by data přišla z ERP nebo z databáze. */
#[AsAlias(id: PriceList::class)]
final class InMemoryPriceList implements PriceList
{
    public const PRODUCTS = [
        '01920000-0000-7000-8000-0000000000a1' => ['name' => 'Kniha o DDD', 'price' => 79_900],
        '01920000-0000-7000-8000-0000000000a2' => ['name' => 'Workshop Event Storming', 'price' => 490_000],
        '01920000-0000-7000-8000-0000000000a3' => ['name' => 'Samolepka', 'price' => 4_900],
    ];

    public const WHOLESALE_CUSTOMER = '01920000-0000-7000-8000-000000000002';

    public function unitPriceOf(ProductId $productId): Money
    {
        $product = self::PRODUCTS[$productId->value]
            ?? throw new \OutOfBoundsException(sprintf('Produkt „%s“ není v ceníku.', $productId->value));

        return new Money($product['price'], Currency::CZK);
    }

    public function discountPercentFor(CustomerId $customerId): int
    {
        // Velkoodběratelská skupina má desetiprocentní slevu.
        return $customerId->value === self::WHOLESALE_CUSTOMER ? 10 : 0;
    }
}
