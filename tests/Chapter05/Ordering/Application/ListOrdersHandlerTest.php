<?php

declare(strict_types=1);

namespace App\Tests\Chapter05\Ordering\Application;

use App\Chapter05_CQRS\Ordering\Application\IntegrationEvent\OrderPlacedIntegrationEvent;
use App\Chapter05_CQRS\Ordering\Application\Query\ListOrders;
use App\Chapter05_CQRS\Ordering\Application\Query\ListOrdersHandler;
use App\Chapter05_CQRS\Ordering\Application\ViewModel\OrderSummaryViewModel;
use App\Chapter05_CQRS\Ordering\Domain\Event\OrderShipped;
use App\Chapter05_CQRS\Ordering\Domain\ValueObject\OrderId;
use App\Chapter05_CQRS\Ordering\Infrastructure\Projection\OrderDashboardProjector;
use App\Chapter05_CQRS\Shipping\Domain\ValueObject\ShipmentId;
use App\Tests\Chapter05\Ordering\DashboardDatabase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListOrdersHandlerTest extends TestCase
{
    private const JANA = '01920000-0000-7000-8000-000000000001';
    private const PETR = '01920000-0000-7000-8000-000000000002';

    private Connection $connection;
    private ListOrdersHandler $handler;
    /** @var array<string, string> */
    private array $orderIds = [];

    protected function setUp(): void
    {
        $this->connection = DashboardDatabase::connection();
        $this->handler = new ListOrdersHandler($this->connection);

        // Read model se plní projektorem, stejně jako v aplikaci.
        $projector = new OrderDashboardProjector($this->connection);
        foreach ([
            'first' => [self::JANA, 1000, '2026-03-01 10:00:00'],
            'second' => [self::JANA, 3000, '2026-03-02 10:00:00'],
            'third' => [self::JANA, 2000, '2026-03-03 10:00:00'],
            'foreign' => [self::PETR, 9900, '2026-03-04 10:00:00'],
        ] as $key => [$customerId, $total, $at]) {
            $this->orderIds[$key] = OrderId::generate()->value;
            $projector(new OrderPlacedIntegrationEvent(
                eventId: Uuid::v7(),
                orderId: $this->orderIds[$key],
                customerId: $customerId,
                items: [],
                totalAmountCents: $total,
                occurredAt: new \DateTimeImmutable($at),
            ));
        }

        $projector(new OrderShipped(
            orderId: OrderId::fromString($this->orderIds['second']),
            shipmentId: ShipmentId::generate(),
            occurredAt: new \DateTimeImmutable('2026-03-05 10:00:00'),
        ));
    }

    /** @param list<OrderSummaryViewModel> $rows */
    private function ids(array $rows): array
    {
        return array_map(static fn (OrderSummaryViewModel $row): string => $row->orderId, $rows);
    }

    public function test_lists_only_orders_of_given_customer_newest_first(): void
    {
        $rows = ($this->handler)(new ListOrders(self::JANA));

        self::assertSame(
            [$this->orderIds['third'], $this->orderIds['second'], $this->orderIds['first']],
            $this->ids($rows),
        );
        self::assertContainsOnlyInstancesOf(OrderSummaryViewModel::class, $rows);
    }

    public function test_filters_by_status_of_the_read_model(): void
    {
        $rows = ($this->handler)(new ListOrders(self::JANA, status: 'shipped'));

        self::assertSame([$this->orderIds['second']], $this->ids($rows));
        self::assertNotNull($rows[0]->shipmentId);
    }

    public function test_sorts_and_pages(): void
    {
        $rows = ($this->handler)(new ListOrders(
            self::JANA,
            limit: 2,
            offset: 1,
            sortBy: 'totalAmount',
            sortDirection: 'ASC',
        ));

        self::assertSame([$this->orderIds['third'], $this->orderIds['second']], $this->ids($rows));
    }

    public function test_rejects_unknown_sort_column(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->handler)(new ListOrders(self::JANA, sortBy: 'total_amount; DROP TABLE ch05_order_dashboard'));
    }
}
