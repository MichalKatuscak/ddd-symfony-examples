<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\Customer;
use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Chapter03_BasicConcepts\Domain\Service\ShippingFeeService;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class ShippingFeeServiceTest extends TestCase
{
    public function test_regular_customer_with_small_order_pays_flat_fee(): void
    {
        $fee = (new ShippingFeeService())->feeFor($this->orderWithLines(4), $this->customer(vip: false));

        self::assertTrue($fee->equals(new Money(99_00, Currency::CZK)));
    }

    public function test_vip_customer_ships_for_free(): void
    {
        $fee = (new ShippingFeeService())->feeFor($this->orderWithLines(1), $this->customer(vip: true));

        self::assertTrue($fee->equals(Money::zero(Currency::CZK)));
    }

    public function test_five_items_ship_for_free(): void
    {
        $fee = (new ShippingFeeService())->feeFor($this->orderWithLines(5), $this->customer(vip: false));

        self::assertTrue($fee->equals(Money::zero(Currency::CZK)));
    }

    private function orderWithLines(int $lines): Order
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        for ($i = 0; $i < $lines; ++$i) {
            $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));
        }

        return $order;
    }

    private function customer(bool $vip): Customer
    {
        return new Customer(CustomerId::generate(), $vip);
    }
}
