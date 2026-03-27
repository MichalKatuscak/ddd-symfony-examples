<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Application\PlaceOrder;

use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;
use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
final class PlaceOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(PlaceOrderCommand $command): string
    {
        $id = OrderId::generate();
        $order = Order::place($id, $command->customerId, $command->items);
        $this->orders->save($order);

        foreach ($order->pullEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $id->value;
    }
}
