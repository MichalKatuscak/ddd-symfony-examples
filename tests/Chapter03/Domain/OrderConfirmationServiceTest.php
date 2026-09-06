<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Domain;

use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Shared\Domain\Currency;
use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
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
        $order = Order::place(OrderId::generate(), CustomerId::fromString('zákazník-1'));
        $order->addItem(ProductId::generate(), 2, new Money(59900, Currency::CZK));

        $this->service->confirm($order);

        $this->assertSame(OrderStatus::Confirmed, $order->status);
    }

    public function test_confirmed_order_is_saved_in_repository(): void
    {
        $id = OrderId::generate();
        $order = Order::place($id, CustomerId::fromString('zákazník-1'));
        $order->addItem(ProductId::generate(), 1, new Money(10000, Currency::CZK));

        $this->service->confirm($order);

        $found = $this->repository->findById($id);
        $this->assertNotNull($found);
        $this->assertSame(OrderStatus::Confirmed, $found->status);
    }

    public function test_confirm_empty_order_throws_domain_exception(): void
    {
        $this->expectException(\DomainException::class);
        $order = Order::place(OrderId::generate(), CustomerId::fromString('zákazník-1'));
        $this->service->confirm($order);
    }
}
