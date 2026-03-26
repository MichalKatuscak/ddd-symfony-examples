<?php

namespace App\Chapter03_BasicConcepts\Domain\Repository;

use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(OrderId $id): ?Order;
    /** @return Order[] */
    public function findAll(): array;
}
