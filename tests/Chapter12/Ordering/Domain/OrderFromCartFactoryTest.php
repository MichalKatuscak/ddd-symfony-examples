<?php

declare(strict_types=1);

namespace App\Tests\Chapter12\Ordering\Domain;

use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\Cart;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartId;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartLine;
use App\Chapter12_LesserPatterns\Ordering\Domain\Factory\OrderFromCartFactory;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\OrderType;
use App\Chapter12_LesserPatterns\Ordering\Domain\Service\PricingService;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ProductId;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\ShippingAddress;
use App\Chapter12_LesserPatterns\Ordering\Infrastructure\InMemoryPriceList;
use App\Chapter12_LesserPatterns\Ordering\Infrastructure\Repository\InMemoryCartRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class OrderFromCartFactoryTest extends TestCase
{
    private const BOOK = '01920000-0000-7000-8000-0000000000a1';

    private InMemoryCartRepository $carts;
    private OrderFromCartFactory $factory;

    protected function setUp(): void
    {
        $this->carts = new InMemoryCartRepository();
        $this->factory = new OrderFromCartFactory(
            $this->carts,
            new PricingService(new InMemoryPriceList()),
            new MockClock('2026-03-01 10:00:00'),
        );
    }

    /** @param list<CartLine> $lines */
    private function cart(array $lines): CartId
    {
        $cart = new Cart(CartId::generate(), $lines, new ShippingAddress('Ukázková 1', 'Město', '110 00', 'CZ'));
        $this->carts->save($cart);

        return $cart->id;
    }

    public function test_builds_physical_order_priced_from_price_list(): void
    {
        $cartId = $this->cart([new CartLine(ProductId::fromString(self::BOOK), 2)]);

        $order = $this->factory->fromCart($cartId, CustomerId::generate());

        self::assertSame(OrderType::Physical, $order->type());
        self::assertSame(79_900, $order->items()[0]->unitPrice->amountInCents);
        self::assertSame(159_800, $order->totalAmount()->amountInCents);
        self::assertSame('CZ', $order->shippingAddress?->countryCode);
        self::assertEquals(new \DateTimeImmutable('2026-03-01 10:00:00'), $order->placedAt());
    }

    public function test_wholesale_customer_gets_group_discount(): void
    {
        $cartId = $this->cart([new CartLine(ProductId::fromString(self::BOOK), 1)]);

        $order = $this->factory->fromCart($cartId, CustomerId::fromString(InMemoryPriceList::WHOLESALE_CUSTOMER));

        self::assertSame(71_910, $order->items()[0]->unitPrice->amountInCents);
    }

    public function test_empty_cart_is_rejected_before_the_aggregate(): void
    {
        $cartId = $this->cart([]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot place order from empty cart.');

        $this->factory->fromCart($cartId, CustomerId::generate());
    }
}
