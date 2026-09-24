<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Infrastructure;

use App\Chapter11_OutboxPattern\Ordering\Domain\Exception\OrderNotFoundException;
use App\Chapter11_OutboxPattern\Ordering\Domain\Model\Order;
use App\Chapter11_OutboxPattern\Ordering\Domain\Repository\OrderRepository;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;

final class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> */
    private array $orders = [];

    public function save(Order $order): void
    {
        $this->orders[$order->id->value] = $order;
    }

    public function get(OrderId $id): Order
    {
        return $this->orders[$id->value] ?? throw OrderNotFoundException::withId($id);
    }

    public function all(): array
    {
        return array_values($this->orders);
    }
}
