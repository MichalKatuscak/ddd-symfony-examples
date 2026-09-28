<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\Ordering\Application\Query;

use App\Chapter05_CQRS\Ordering\Application\ViewModel\OrderSummaryViewModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Query handler čte z denormalizované tabulky přes DBAL a doménový model
 * záměrně obchází: agregát není optimalizovaný pro čtení.
 */
#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class ListOrdersHandler
{
    /**
     * Řazení přichází zvenku jako řetězec. Do SQL smí jen hodnota
     * z tohoto seznamu, nikdy vstup sám – jinak je to SQL injection.
     */
    private const array SORTABLE = [
        'createdAt' => 'placed_at',
        'updatedAt' => 'updated_at',
        'totalAmount' => 'total_amount',
    ];

    public function __construct(
        private Connection $connection,
    ) {}

    /** @return list<OrderSummaryViewModel> */
    public function __invoke(ListOrders $query): array
    {
        $column = self::SORTABLE[$query->sortBy]
            ?? throw new \InvalidArgumentException(sprintf('Cannot sort by "%s".', $query->sortBy));
        $direction = strtoupper($query->sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->connection->createQueryBuilder()
            ->select('order_id', 'status', 'total_amount', 'shipment_id', 'placed_at')
            ->from('ch05_order_dashboard')
            ->where('customer_id = :customerId')
            ->setParameter('customerId', $query->customerId)
            ->orderBy($column, $direction)
            ->setMaxResults($query->limit)
            ->setFirstResult($query->offset);

        if ($query->status !== null) {
            $qb->andWhere('status = :status')->setParameter('status', $query->status);
        }

        return array_map(
            static fn (array $row): OrderSummaryViewModel => new OrderSummaryViewModel(
                orderId: $row['order_id'],
                status: $row['status'],
                totalAmountInCents: (int) $row['total_amount'],
                shipmentId: $row['shipment_id'],
                placedAt: new \DateTimeImmutable($row['placed_at']),
            ),
            $qb->executeQuery()->fetchAllAssociative(),
        );
    }
}
