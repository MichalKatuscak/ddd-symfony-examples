<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Domain\Specification;

use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Chapter12_LesserPatterns\Domain\Order\Money;
use App\Chapter12_LesserPatterns\Domain\Order\Order;
use App\Chapter12_LesserPatterns\Domain\Order\OrderEligibleForFreeShipping;
use App\Chapter12_LesserPatterns\Domain\Order\OrderItem;
use PHPUnit\Framework\TestCase;

final class OrderEligibleForFreeShippingTest extends TestCase
{
    public function test_order_at_or_above_threshold_is_eligible(): void
    {
        $order = $this->orderTotallingMinorUnits(150_000); // 1500 CZK
        $spec = new OrderEligibleForFreeShipping(new Money(100_000));

        $this->assertTrue($spec->isSatisfiedBy($order));
    }

    public function test_order_below_threshold_is_not_eligible(): void
    {
        $order = $this->orderTotallingMinorUnits(50_000); // 500 CZK
        $spec = new OrderEligibleForFreeShipping(new Money(100_000));

        $this->assertFalse($spec->isSatisfiedBy($order));
    }

    public function test_non_order_candidate_does_not_satisfy_spec(): void
    {
        $spec = new OrderEligibleForFreeShipping(new Money(100_000));

        $this->assertFalse($spec->isSatisfiedBy(new \stdClass()));
    }

    private function orderTotallingMinorUnits(int $totalMinorUnits): Order
    {
        return Order::place(
            customerId: new CustomerId('cust-1'),
            items: [new OrderItem('item', 1, new Money($totalMinorUnits))],
            shippingCountry: 'CZ',
        );
    }
}
