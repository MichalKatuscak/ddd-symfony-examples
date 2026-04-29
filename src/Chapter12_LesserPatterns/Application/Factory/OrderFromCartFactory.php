<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Application\Factory;

use App\Chapter12_LesserPatterns\Domain\Customer\CustomerRepositoryInterface;
use App\Chapter12_LesserPatterns\Domain\Order\CartRepositoryInterface;
use App\Chapter12_LesserPatterns\Domain\Order\Order;

/**
 * Factory class — vznik objednávky z košíku vyžaduje injektované závislosti
 * (cart repo + customer repo + pricing service). Statickou metodou na Order
 * by tyto závislosti nemohly být přijaty bez service locatoru.
 *
 * Vernon (2013): "Preferujte named constructor. Sahejte po samostatné Factory
 * class teprve tehdy, když vznik nutně potřebuje DI závislosti."
 *
 * Pozor — Factory class uvnitř volá Order::place(), invariant "alespoň 1
 * položka" zůstává v agregátu. Factory řeší jen orchestraci vstupních dat.
 */
final class OrderFromCartFactory
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly CustomerRepositoryInterface $customers,
        private readonly PricingService $pricing,
    ) {}

    public function fromCart(string $cartId): Order
    {
        $cart = $this->carts->findById($cartId);

        if ($cart->isEmpty()) {
            throw new \DomainException('Cannot place order from empty cart.');
        }

        $customer = $this->customers->findById($cart->customerId())
            ?? throw new \DomainException(
                sprintf('Customer %s not found', $cart->customerId()->value),
            );

        $pricedItems = $this->pricing->priceItems($cart->rawItems(), $customer);

        return Order::place(
            customerId: $customer->id(),
            items: $pricedItems,
            shippingCountry: $cart->shippingCountry(),
        );
    }
}
