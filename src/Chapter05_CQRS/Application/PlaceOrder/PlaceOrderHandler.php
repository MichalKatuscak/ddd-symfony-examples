<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Application\PlaceOrder;

use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
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
