<?php

declare(strict_types=1);

namespace App\Tests\Chapter04\Domain;

use App\Chapter04_Implementation\Domain\Order\Money;
use App\Chapter04_Implementation\Domain\Service\OrderPricingService;
use PHPUnit\Framework\TestCase;

final class OrderPricingServiceTest extends TestCase
{
    private OrderPricingService $service;

    protected function setUp(): void
    {
        $this->service = new OrderPricingService();
    }

    public function test_one_item_no_discount(): void
    {
        $price = new Money(100000, 'CZK');
        $result = $this->service->applyVolumeDiscount($price, 1);
        $this->assertEquals($price, $result);
    }

    public function test_two_items_five_percent_discount(): void
    {
        $price = new Money(100000, 'CZK');
        $result = $this->service->applyVolumeDiscount($price, 2);
        $this->assertEquals(new Money(95000, 'CZK'), $result);
    }

    public function test_three_items_ten_percent_discount(): void
    {
        $price = new Money(100000, 'CZK');
        $result = $this->service->applyVolumeDiscount($price, 3);
        $this->assertEquals(new Money(90000, 'CZK'), $result);
    }

    public function test_more_than_three_items_ten_percent_discount(): void
    {
        $price = new Money(100000, 'CZK');
        $result = $this->service->applyVolumeDiscount($price, 5);
        $this->assertEquals(new Money(90000, 'CZK'), $result);
    }

    public function test_exactly_two_items_boundary(): void
    {
        $price = new Money(200000, 'CZK');
        $result = $this->service->applyVolumeDiscount($price, 2);
        $this->assertEquals(new Money(190000, 'CZK'), $result);
    }
}
