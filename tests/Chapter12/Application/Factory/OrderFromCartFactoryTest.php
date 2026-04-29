<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Application\Factory;

use App\Chapter12_LesserPatterns\Application\Factory\OrderFromCartFactory;
use App\Chapter12_LesserPatterns\Application\Factory\PricingService;
use App\Chapter12_LesserPatterns\Domain\Customer\Customer;
use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Chapter12_LesserPatterns\Domain\Order\Cart;
use App\Chapter12_LesserPatterns\Infrastructure\InMemoryCartRepository;
use App\Chapter12_LesserPatterns\Infrastructure\InMemoryCustomerRepository;
use PHPUnit\Framework\TestCase;

final class OrderFromCartFactoryTest extends TestCase
{
    public function test_factory_uses_injected_dependencies_to_build_order(): void
    {
        $customerId = new CustomerId('cust-1');
        $customers = new InMemoryCustomerRepository();
        $customers->save(new Customer($customerId, 'a@b.cz', 'CZ', premium: false));

        $carts = new InMemoryCartRepository();
        $carts->save(new Cart(
            id: 'cart-1',
            customerId: $customerId,
            rawItems: [['name' => 'A', 'quantity' => 2, 'unitPriceMinorUnits' => 50_000]],
            shippingCountry: 'CZ',
        ));

        $factory = new OrderFromCartFactory($carts, $customers, new PricingService());

        $order = $factory->fromCart('cart-1');

        $this->assertSame($customerId->value, $order->customerId()->value);
        $this->assertSame(100_000, $order->total()->amount); // 2 × 500 CZK, no discount
        $this->assertCount(1, $order->items());
    }

    public function test_premium_customer_gets_discount_applied_by_pricing_service(): void
    {
        $customerId = new CustomerId('cust-2');
        $customers = new InMemoryCustomerRepository();
        $customers->save(new Customer($customerId, 'p@b.cz', 'CZ', premium: true));

        $carts = new InMemoryCartRepository();
        $carts->save(new Cart(
            id: 'cart-2',
            customerId: $customerId,
            rawItems: [['name' => 'A', 'quantity' => 1, 'unitPriceMinorUnits' => 100_000]],
            shippingCountry: 'CZ',
        ));

        $factory = new OrderFromCartFactory($carts, $customers, new PricingService());

        $order = $factory->fromCart('cart-2');

        // 10 % sleva v PricingService
        $this->assertSame(90_000, $order->total()->amount);
    }

    public function test_empty_cart_throws_domain_exception(): void
    {
        $customerId = new CustomerId('cust-3');
        $customers = new InMemoryCustomerRepository();
        $customers->save(new Customer($customerId, 'e@b.cz', 'CZ'));

        $carts = new InMemoryCartRepository();
        $carts->save(new Cart(
            id: 'cart-empty',
            customerId: $customerId,
            rawItems: [],
            shippingCountry: 'CZ',
        ));

        $factory = new OrderFromCartFactory($carts, $customers, new PricingService());

        $this->expectException(\DomainException::class);
        $factory->fromCart('cart-empty');
    }
}
