<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Application;

use App\Chapter12_LesserPatterns\Application\EvaluatePromo\EvaluatePromoCommand;
use App\Chapter12_LesserPatterns\Application\EvaluatePromo\EvaluatePromoHandler;
use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Chapter12_LesserPatterns\Domain\Order\Money;
use App\Chapter12_LesserPatterns\Domain\Order\Order;
use App\Chapter12_LesserPatterns\Domain\Order\OrderItem;
use App\Chapter12_LesserPatterns\Infrastructure\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

final class EvaluatePromoHandlerTest extends TestCase
{
    public function test_eu_order_above_threshold_qualifies_for_promo(): void
    {
        $repo = new InMemoryOrderRepository();
        $order = $this->placeOrder(150_000, 'CZ');
        $repo->save($order);

        $handler = new EvaluatePromoHandler($repo);
        $result = ($handler)(new EvaluatePromoCommand(
            orderId: $order->id()->value,
            freeShippingThresholdMinorUnits: 100_000,
        ));

        $this->assertTrue($result->eligible);
        $this->assertTrue($result->aboveFreeShippingThreshold);
        $this->assertTrue($result->shipsToEU);
        $this->assertTrue($result->notInBlacklist);
    }

    public function test_us_order_fails_eu_country_subspec(): void
    {
        $repo = new InMemoryOrderRepository();
        $order = $this->placeOrder(150_000, 'US');
        $repo->save($order);

        $handler = new EvaluatePromoHandler($repo);
        $result = ($handler)(new EvaluatePromoCommand(
            orderId: $order->id()->value,
            freeShippingThresholdMinorUnits: 100_000,
        ));

        $this->assertFalse($result->eligible);
        $this->assertTrue($result->aboveFreeShippingThreshold);
        $this->assertFalse($result->shipsToEU);
        $this->assertTrue($result->notInBlacklist);
    }

    public function test_blacklisted_country_is_caught_by_third_subspec(): void
    {
        $repo = new InMemoryOrderRepository();
        $order = $this->placeOrder(150_000, 'KP');
        $repo->save($order);

        $handler = new EvaluatePromoHandler($repo);
        $result = ($handler)(new EvaluatePromoCommand(
            orderId: $order->id()->value,
            freeShippingThresholdMinorUnits: 100_000,
        ));

        $this->assertFalse($result->eligible);
        $this->assertFalse($result->notInBlacklist);
    }

    public function test_below_threshold_fails_first_subspec(): void
    {
        $repo = new InMemoryOrderRepository();
        $order = $this->placeOrder(50_000, 'CZ');
        $repo->save($order);

        $handler = new EvaluatePromoHandler($repo);
        $result = ($handler)(new EvaluatePromoCommand(
            orderId: $order->id()->value,
            freeShippingThresholdMinorUnits: 100_000,
        ));

        $this->assertFalse($result->eligible);
        $this->assertFalse($result->aboveFreeShippingThreshold);
        $this->assertTrue($result->shipsToEU);
    }

    private function placeOrder(int $totalMinorUnits, string $country): Order
    {
        return Order::place(
            customerId: new CustomerId('cust-1'),
            items: [new OrderItem('item', 1, new Money($totalMinorUnits))],
            shippingCountry: $country,
        );
    }
}
