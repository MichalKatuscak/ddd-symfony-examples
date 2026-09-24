<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Ordering\Domain\Repository;

use App\Chapter11_OutboxPattern\Ordering\Domain\Exception\OrderNotFoundException;
use App\Chapter11_OutboxPattern\Ordering\Domain\Model\Order;
use App\Chapter11_OutboxPattern\Ordering\Domain\ValueObject\OrderId;

interface OrderRepository
{
    public function save(Order $order): void;

    /** @throws OrderNotFoundException */
    public function get(OrderId $id): Order;

    /** @return list<Order> */
    public function all(): array;
}
