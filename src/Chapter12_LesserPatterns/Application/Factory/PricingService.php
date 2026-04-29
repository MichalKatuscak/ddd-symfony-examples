<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Application\Factory;

use App\Chapter12_LesserPatterns\Domain\Customer\Customer;
use App\Chapter12_LesserPatterns\Domain\Order\Money;
use App\Chapter12_LesserPatterns\Domain\Order\OrderItem;

/**
 * Stub pricing engine — v reálné aplikaci by aplikoval slevy, kupóny,
 * ceník pro segment zákazníka. Pro účely demonstrace Factory class:
 * stačí, že OrderFromCartFactory tuto závislost potřebuje a static
 * metoda na Order ji nemůže přijmout.
 */
final class PricingService
{
    /**
     * @param list<array{name: string, quantity: int, unitPriceMinorUnits: int}> $rawItems
     * @return list<OrderItem>
     */
    public function priceItems(array $rawItems, Customer $customer): array
    {
        $discountPercent = $customer->isPremium() ? 10 : 0;

        return array_map(
            static function (array $row) use ($discountPercent): OrderItem {
                $price = $row['unitPriceMinorUnits'];
                if ($discountPercent > 0) {
                    $price = (int) round($price * (100 - $discountPercent) / 100);
                }

                return new OrderItem(
                    name: $row['name'],
                    quantity: $row['quantity'],
                    unitPrice: new Money($price),
                );
            },
            $rawItems,
        );
    }
}
