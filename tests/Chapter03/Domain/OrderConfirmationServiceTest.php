<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\OrderStatus;
use App\Chapter03_BasicConcepts\Domain\Service\OrderConfirmationService;
use App\Chapter03_BasicConcepts\Infrastructure\Persistence\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

final class OrderConfirmationServiceTest extends TestCase
{
    private InMemoryOrderRepository $repository;
    private OrderConfirmationService $service;

    protected function setUp(): void
    {
        $this->repository = new InMemoryOrderRepository();
        $this->service = new OrderConfirmationService($this->repository);
    }

    public function test_confirm_order_with_items_succeeds(): void
    {
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $order->addItem('Symfony kniha', 2, new Money(59900, 'CZK'));

        $this->service->confirm($order);

        $this->assertSame(OrderStatus::Confirmed, $order->status());
    }

    public function test_confirmed_order_is_saved_in_repository(): void
    {
        $id = OrderId::generate();
        $order = Order::create($id, 'zákazník-1');
        $order->addItem('Produkt', 1, new Money(10000, 'CZK'));

        $this->service->confirm($order);

        $found = $this->repository->findById($id);
        $this->assertNotNull($found);
        $this->assertSame(OrderStatus::Confirmed, $found->status());
    }

    public function test_confirm_empty_order_throws_domain_exception(): void
    {
        $this->expectException(\DomainException::class);
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $this->service->confirm($order);
    }
}
