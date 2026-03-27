<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Domain\Repository;

use App\Chapter04_Implementation\Domain\Order\Order;
use App\Chapter04_Implementation\Domain\Order\OrderId;

interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(OrderId $id): ?Order;
    /** @return Order[] */
    public function findAll(): array;
}
