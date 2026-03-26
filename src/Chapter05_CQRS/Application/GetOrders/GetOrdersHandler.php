<?php
namespace App\Chapter05_CQRS\Application\GetOrders;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetOrdersHandler
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /** @return OrderView[] */
    public function __invoke(GetOrdersQuery $query): array
    {
        return array_map(
            fn($order) => new OrderView(
                id: substr($order->id()->value, 0, 8) . '…',
                customerId: $order->customerId(),
                total: $order->total()->formatted(),
                itemCount: count($order->items()),
            ),
            $this->orders->findAll(),
        );
    }
}
