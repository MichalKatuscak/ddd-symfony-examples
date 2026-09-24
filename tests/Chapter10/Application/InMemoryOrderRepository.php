<?php

declare(strict_types=1);

namespace App\Tests\Chapter10\Application;

use App\Chapter02_AggregateDesign\Domain\Order\OrderId;
use App\Chapter10_Authorization\Domain\Order\Exception\OrderNotFoundException;
use App\Chapter10_Authorization\Domain\Order\Order;
use App\Chapter10_Authorization\Domain\Repository\OrderRepository;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public int $saves = 0;

    public function save(Order $order): void
    {
        $this->orders[$order->id->value] = $order;
        ++$this->saves;
    }

    public function get(OrderId $id): Order
    {
        return $this->orders[$id->value] ?? throw OrderNotFoundException::withId($id);
    }
}
