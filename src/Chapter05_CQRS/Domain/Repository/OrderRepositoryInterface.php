<?php
namespace App\Chapter05_CQRS\Domain\Repository;
use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function findById(OrderId $id): ?Order;
    /** @return Order[] */
    public function findAll(): array;
}
