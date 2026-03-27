<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Service;

use App\Chapter04_Implementation\Domain\Order\Money;

final class OrderPricingService
{
    public function applyVolumeDiscount(Money $price, int $itemCount): Money
    {
        if ($itemCount >= 3) {
            return $price->add($price->percentage(-10));
        }
        if ($itemCount >= 2) {
            return $price->add($price->percentage(-5));
        }
        return $price;
    }
}
