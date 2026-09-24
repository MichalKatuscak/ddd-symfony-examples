<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Infrastructure\Repository;

use App\Chapter05_CQRS\Ordering\Domain\Exception\OrderNotFoundException;
use App\Chapter05_CQRS\Ordering\Domain\Model\Order;
use App\Chapter05_CQRS\Ordering\Domain\Repository\OrderRepository;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

/**
 * Write model drží ukázka v paměti procesu: kapitola CQRS je o čtecí
 * straně. Doctrine mapování agregátu ukazuje kapitola Návrh agregátu,
 * repozitář nad EntityManagerem ukázka Chapter04_Implementation.
 */
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
