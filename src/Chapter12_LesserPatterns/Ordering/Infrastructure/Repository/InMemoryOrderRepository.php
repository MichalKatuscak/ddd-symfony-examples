<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Ordering\Infrastructure\Repository;

use App\Chapter12_LesserPatterns\Ordering\Domain\Exception\OrderNotFoundException;
use App\Chapter12_LesserPatterns\Ordering\Domain\Model\Order;
use App\Chapter12_LesserPatterns\Ordering\Domain\Repository\OrderRepository;
use App\Chapter12_LesserPatterns\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: OrderRepository::class)]
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
}
