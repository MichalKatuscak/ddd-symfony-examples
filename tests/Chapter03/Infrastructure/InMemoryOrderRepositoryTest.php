<?php

declare(strict_types=1);

namespace App\Tests\Chapter03\Infrastructure;

use App\Chapter03_BasicConcepts\Domain\Order\CustomerId;
use App\Chapter03_BasicConcepts\Domain\Order\Exception\OrderNotFoundException;
use App\Chapter03_BasicConcepts\Domain\Order\Order;
use App\Chapter03_BasicConcepts\Domain\Order\OrderId;
use App\Chapter03_BasicConcepts\Domain\Order\ProductId;
use App\Chapter03_BasicConcepts\Infrastructure\Persistence\InMemoryOrderRepository;
use App\Shared\Domain\Currency;
use App\Shared\Domain\Money;
use PHPUnit\Framework\TestCase;

final class InMemoryOrderRepositoryTest extends TestCase
{
    private InMemoryOrderRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryOrderRepository();
    }

    public function test_save_and_get_round_trip(): void
    {
        $id = OrderId::generate();
        $order = Order::place($id, CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));

        $this->repository->save($order);

        self::assertSame($order, $this->repository->get(OrderId::fromString($id->value)));
    }

    public function test_missing_order_is_an_error_not_null(): void
    {
        $this->expectException(OrderNotFoundException::class);
        $this->repository->get(OrderId::generate());
    }

    public function test_saving_same_order_twice_overwrites(): void
    {
        $order = Order::place(OrderId::generate(), CustomerId::generate());
        $order->addItem(ProductId::generate(), 1, new Money(10_000, Currency::CZK));

        $this->repository->save($order);
        $order->confirm();
        $this->repository->save($order);

        self::assertTrue($this->repository->get($order->id)->isConfirmed());
    }
}
