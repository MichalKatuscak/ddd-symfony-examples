<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Infrastructure;

use App\Chapter03_BasicConcepts\Domain\Order\Money;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Infrastructure\Persistence\InMemoryOrderRepository;
use PHPUnit\Framework\TestCase;

final class InMemoryOrderRepositoryTest extends TestCase
{
    private InMemoryOrderRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryOrderRepository();
    }

    public function test_save_and_find_by_id_roundtrip(): void
    {
        $id = OrderId::generate();
        $order = Order::create($id, 'zákazník-1');
        $order->addItem('Produkt', 1, new Money(10000, 'CZK'));

        $this->repository->save($order);

        $found = $this->repository->findById($id);
        $this->assertSame($order, $found);
    }

    public function test_find_by_id_returns_null_for_unknown_id(): void
    {
        $unknownId = OrderId::generate();
        $this->assertNull($this->repository->findById($unknownId));
    }

    public function test_find_all_returns_all_saved_orders(): void
    {
        $order1 = Order::create(OrderId::generate(), 'zákazník-1');
        $order1->addItem('Produkt A', 1, new Money(10000, 'CZK'));

        $order2 = Order::create(OrderId::generate(), 'zákazník-2');
        $order2->addItem('Produkt B', 2, new Money(5000, 'CZK'));

        $this->repository->save($order1);
        $this->repository->save($order2);

        $all = $this->repository->findAll();
        $this->assertCount(2, $all);
        $this->assertContains($order1, $all);
        $this->assertContains($order2, $all);
    }

    public function test_find_all_returns_empty_when_no_orders(): void
    {
        $this->assertSame([], $this->repository->findAll());
    }

    public function test_saving_same_order_twice_does_not_duplicate(): void
    {
        $order = Order::create(OrderId::generate(), 'zákazník-1');
        $order->addItem('Produkt', 1, new Money(10000, 'CZK'));

        $this->repository->save($order);
        $this->repository->save($order);

        $this->assertCount(1, $this->repository->findAll());
    }
}
