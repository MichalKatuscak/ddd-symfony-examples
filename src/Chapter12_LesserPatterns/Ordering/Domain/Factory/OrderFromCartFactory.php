<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Domain\Factory;

use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartId;
use App\Chapter12_LesserPatterns\Ordering\Domain\Cart\CartRepository;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\Ordering\Domain\Service\PricingService;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\CustomerId;
use Psr\Clock\ClockInterface;

/**
 * Factory class – vznik objednávky z košíku vyžaduje
 * načtení košíku a aplikaci aktuálního pricingu.
 * Závislosti dodá container, volající je nemusí shánět.
 *
 * Invariant „aspoň 1 položka“ nepřebírá, ten zůstává v placePhysical().
 * Factory výsledek neukládá ani nepublikuje událost – to by z ní byl
 * command handler.
 */
final class OrderFromCartFactory
{
    public function __construct(
        private readonly CartRepository $carts,
        private readonly PricingService $pricing,
        private readonly ClockInterface $clock,
    ) {}

    public function fromCart(CartId $cartId, CustomerId $customer): Order
    {
        $cart = $this->carts->getById($cartId);

        if ($cart->isEmpty()) {
            // Zkratka: v projektu pojmenovaná výjimka, např. EmptyCartException.
            throw new \DomainException('Cannot place order from empty cart.');
        }

        $pricedItems = $this->pricing->priceItems($cart->items(), $customer);

        return Order::placePhysical(
            customerId: $customer,
            items: $pricedItems,
            placedAt: $this->clock->now(),
            // Navíc proti knize: adresu specifikace potřebují, košík ji zná.
            shippingAddress: $cart->shippingAddress(),
        );
    }
}
