<?php
declare(strict_types=1);
namespace App\Chapter03_BasicConcepts\Domain\Service;

use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Repository\OrderRepositoryInterface;

final class OrderConfirmationService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function confirm(Order $order): void
    {
        $order->confirm();
        $this->orders->save($order);
    }
}
