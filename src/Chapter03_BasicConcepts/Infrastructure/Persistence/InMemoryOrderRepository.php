<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Infrastructure\Persistence;

use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Repository\OrderRepositoryInterface;

final class InMemoryOrderRepository implements OrderRepositoryInterface
{
    /** @var Order[] */
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
