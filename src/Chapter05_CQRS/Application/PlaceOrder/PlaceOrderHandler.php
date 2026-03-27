<?php

declare(strict_types=1);
namespace App\Chapter05_CQRS\Application\PlaceOrder;
use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class PlaceOrderHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function __invoke(PlaceOrderCommand $command): string
    {
        $id = OrderId::generate();
        $order = Order::place($id, $command->customerId, $command->items);
        $this->orders->save($order);
        return $id->value;
    }
}
