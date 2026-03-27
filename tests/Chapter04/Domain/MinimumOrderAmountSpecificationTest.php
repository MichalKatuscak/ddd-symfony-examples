<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\Domain;

use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Order\OrderLine;
use App\Chapter04_Implementation\Domain\Specification\MinimumOrderAmountSpecification;
use PHPUnit\Framework\TestCase;

final class MinimumOrderAmountSpecificationTest extends TestCase
{
    public function testOrderMeetsMinimumAmount(): void
    {
        $spec = new MinimumOrderAmountSpecification(10000); // 100 CZK
        $order = Order::place(
            OrderId::generate(),
            'customer-1',
            [new OrderLine('Produkt', 2, new Money(10000, 'CZK'))], // 200 CZK
        );

        $this->assertTrue($spec->isSatisfiedBy($order));
    }

    public function testOrderBelowMinimumAmount(): void
    {
        $spec = new MinimumOrderAmountSpecification(50000); // 500 CZK
        $order = Order::place(
            OrderId::generate(),
            'customer-1',
            [new OrderLine('Produkt', 1, new Money(10000, 'CZK'))], // 100 CZK
        );

        $this->assertFalse($spec->isSatisfiedBy($order));
    }

    public function testExactMinimumAmountSatisfies(): void
    {
        $spec = new MinimumOrderAmountSpecification(10000);
        $order = Order::place(
            OrderId::generate(),
            'customer-1',
            [new OrderLine('Produkt', 1, new Money(10000, 'CZK'))],
        );

        $this->assertTrue($spec->isSatisfiedBy($order));
    }
}
