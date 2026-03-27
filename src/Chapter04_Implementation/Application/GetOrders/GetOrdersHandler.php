<?php

declare(strict_types=1);

namespace App\Chapter04_Implementation\Application\GetOrders;

use App\Chapter04_Implementation\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetOrdersHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /** @return \App\Chapter04_Implementation\Domain\Order\Order[] */
    public function __invoke(GetOrdersQuery $query): array
    {
        return $this->orders->findAll();
    }
}
