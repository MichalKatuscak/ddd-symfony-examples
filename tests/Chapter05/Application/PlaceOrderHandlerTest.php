<?php

namespace App\Tests\Chapter05\Application;

use App\Chapter05_CQRS\Application\PlaceOrder\PlaceOrderCommand;
use App\Chapter05_CQRS\Application\PlaceOrder\PlaceOrderHandler;
use App\Chapter05_CQRS\Domain\Order\Order;
use App\Chapter05_CQRS\Domain\Order\OrderId;
use App\Chapter05_CQRS\Domain\Repository\OrderRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PlaceOrderHandlerTest extends TestCase
{
    public function test_places_order_and_returns_id(): void
    {
        $repo = new class implements OrderRepositoryInterface {
            public ?Order $saved = null;
            public function save(Order $order): void { $this->saved = $order; }
            public function findById(OrderId $id): ?Order { return null; }
            public function findAll(): array { return []; }
        };

        $handler = new PlaceOrderHandler($repo);
        $orderId = ($handler)(new PlaceOrderCommand(
            customerId: 'zákazník-1',
            items: [['name' => 'Symfony kniha', 'qty' => 1, 'price' => 59900]],
        ));

        $this->assertNotEmpty($orderId);
        $this->assertNotNull($repo->saved);
        $this->assertSame($orderId, $repo->saved->id()->value);
    }
}
