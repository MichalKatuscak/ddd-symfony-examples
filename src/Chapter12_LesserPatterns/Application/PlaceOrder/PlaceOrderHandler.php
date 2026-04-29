<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Application\PlaceOrder;

use App\Chapter12_LesserPatterns\Domain\Customer\CustomerId;
use App\Chapter12_LesserPatterns\Domain\Order\Money;
use App\Chapter12_LesserPatterns\Domain\Order\Order;
use App\Chapter12_LesserPatterns\Domain\Order\OrderItem;
use App\Chapter12_LesserPatterns\Domain\Order\OrderRepositoryInterface;

/**
 * Application Service (command handler). Orchestruje use case:
 * převede primitivní DTO na doménové typy, zavolá factory metodu,
 * uloží agregát.
 *
 * Pozor — toto NENÍ Domain Service: drží závislost na repozitáři
 * (perzistence) a pracuje na úrovni aplikační koordinace.
 */
final class PlaceOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function __invoke(PlaceOrderCommand $command): string
    {
        $items = array_map(
            static fn (array $row): OrderItem => new OrderItem(
                name: $row['name'],
                quantity: $row['quantity'],
                unitPrice: new Money($row['unitPriceMinorUnits']),
            ),
            $command->items,
        );

        $order = Order::place(
            customerId: new CustomerId($command->customerId),
            items: array_values($items),
            shippingCountry: $command->shippingCountry,
        );

        $this->orders->save($order);

        return $order->id()->value;
    }
}
