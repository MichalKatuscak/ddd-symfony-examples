<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Service;

use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartLine;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\OrderItem;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;

/**
 * Domain Service – pricing engine z 08.03: cenu položky počítá z ceníku
 * a cenové skupiny zákazníka. Výpočet nepatří košíku, zákazníkovi ani
 * objednávce.
 *
 * Bezstavová, bez perzistence. Závisí jen na doménovém rozhraní PriceList.
 */
final class PricingService
{
    public function __construct(private readonly PriceList $priceList) {}

    /**
     * @param list<CartLine> $lines
     * @return list<OrderItem>
     */
    public function priceItems(array $lines, CustomerId $customer): array
    {
        $discount = $this->priceList->discountPercentFor($customer);

        return array_map(
            function (CartLine $line) use ($discount): OrderItem {
                $price = $this->priceList->unitPriceOf($line->productId);

                return new OrderItem(
                    $line->productId,
                    $line->quantity,
                    $price->subtract($price->percentage($discount)),
                );
            },
            $lines,
        );
    }
}
