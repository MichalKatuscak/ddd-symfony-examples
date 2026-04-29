<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Infrastructure;

use App\Chapter11_OutboxPattern\Domain\Order\Order;
use App\Chapter11_OutboxPattern\Domain\Order\OrderId;
use App\Chapter11_OutboxPattern\Domain\Order\OrderRepositoryInterface;

final class InMemoryOrderRepository implements OrderRepositoryInterface
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[$order->id()->value] = $order;
    }

    public function findById(OrderId $id): ?Order
    {
        return $this->orders[$id->value] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->orders);
    }
}
