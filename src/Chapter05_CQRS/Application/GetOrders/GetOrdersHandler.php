<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Application\GetOrders;

use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetOrdersHandler
{
    public function __construct(private readonly Connection $connection) {}

    /** @return OrderView[] */
    public function __invoke(GetOrdersQuery $query): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, customer_id, total_amount, items FROM ch05_orders ORDER BY rowid DESC'
        );

        return array_map(fn(array $row) => new OrderView(
            id: substr($row['id'], 0, 8) . '…',
            customerId: $row['customer_id'],
            total: number_format($row['total_amount'] / 100, 2) . ' CZK',
            itemCount: count(json_decode($row['items'], true)),
        ), $rows);
    }
}
