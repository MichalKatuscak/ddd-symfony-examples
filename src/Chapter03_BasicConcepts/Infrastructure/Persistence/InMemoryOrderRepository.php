<?php

declare(strict_types=1);

namespace App\Chapter03_BasicConcepts\Infrastructure\Persistence;

use App\Chapter03_BasicConcepts\Domain\Order\Exception\OrderNotFoundException;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Repository\OrderRepository;

/**
 * Implementace pro testy a ukázku. Doménová vrstva nepozná, jestli
 * agregát žije v paměti, nebo v databázi.
 */
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
