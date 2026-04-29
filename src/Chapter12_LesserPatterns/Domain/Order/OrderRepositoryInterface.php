<?php

declare(strict_types=1);

namespace App\Chapter12_LesserPatterns\Domain\Order;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;

    public function findById(OrderId $id): ?Order;

    /** @return list<Order> */
    public function findAll(): array;
}
