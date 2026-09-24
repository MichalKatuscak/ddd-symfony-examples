<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Domain\Repository;

use App\Chapter05_CQRS\Ordering\Domain\Model\Order;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;

interface OrderRepository
{
    public function save(Order $order): void;

    public function get(OrderId $id): Order;
}
