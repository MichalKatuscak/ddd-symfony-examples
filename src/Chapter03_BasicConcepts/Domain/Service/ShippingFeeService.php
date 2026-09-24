<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Domain\Service;

use App\Chapter03_BasicConcepts\Domain\Order\Customer;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;

/**
 * Doménová služba: pravidlo „doprava zdarma pro VIP zákazníky a velké
 * objednávky“ čte data dvou agregátů a nepatří ani jednomu z nich.
 * Nedrží stav a nezávisí na repozitáři ani databázi.
 */
final class ShippingFeeService
{
    // Počítají se řádky objednávky, ne kusy zboží.
    private const int FREE_SHIPPING_FROM_LINES = 5;
    private const int FLAT_FEE_CENTS = 99_00;

    public function feeFor(Order $order, Customer $customer): Money
    {
        $freeShipping = $customer->isVip()
            || count($order->items()) >= self::FREE_SHIPPING_FROM_LINES;

        return $freeShipping
            ? Money::zero(Currency::CZK)
            : new Money(self::FLAT_FEE_CENTS, Currency::CZK);
    }
}
