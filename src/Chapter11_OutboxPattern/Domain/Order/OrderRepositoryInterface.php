<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function findById(OrderId $id): ?Order;

    /**
     * @return Order[]
     */
    public function findAll(): array;
}
