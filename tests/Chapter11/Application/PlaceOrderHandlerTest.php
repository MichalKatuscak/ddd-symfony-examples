<?php

declare(strict_types=1);

namespace App\Tests\Chapter11\Application;

use App\Chapter11_OutboxPattern\Application\PlaceOrder\PlaceOrderCommand;
use App\Chapter11_OutboxPattern\Application\PlaceOrder\PlaceOrderHandler;
use App\Chapter11_OutboxPattern\Domain\Order\OrderId;
use App\Chapter11_OutboxPattern\Domain\Order\OrderPlaced;
use App\Chapter11_OutboxPattern\Domain\Outbox\OutboxStatus;
use App\Chapter11_OutboxPattern\Infrastructure\InMemoryOrderRepository;
use App\Chapter11_OutboxPattern\Infrastructure\InMemoryOutboxRepository;
use PHPUnit\Framework\TestCase;

final class PlaceOrderHandlerTest extends TestCase
{
    private InMemoryOrderRepository $orders;
    private InMemoryOutboxRepository $outbox;
    private PlaceOrderHandler $handler;

    protected function setUp(): void
    {
        $this->orders = new InMemoryOrderRepository();
        $this->outbox = new InMemoryOutboxRepository();
        $this->handler = new PlaceOrderHandler($this->orders, $this->outbox);
    }

    public function test_place_order_persists_aggregate_and_outbox_row_atomically(): void
    {
        $orderId = ($this->handler)(new PlaceOrderCommand(
            customerId: 'customer-1',
            amount: 2500,
        ));

        // Order saved
        $order = $this->orders->findById(new OrderId($orderId));
        $this->assertNotNull($order);
        $this->assertSame('customer-1', $order->customerId());

        // Outbox row written in the same conceptual transaction
        $messages = $this->outbox->findAll();
        $this->assertCount(1, $messages);
        $this->assertSame(OrderPlaced::class, $messages[0]->type);
        $this->assertSame(OutboxStatus::Pending, $messages[0]->status());
        $this->assertSame($orderId, $messages[0]->payload['orderId']);
        $this->assertSame(2500, $messages[0]->payload['amount']);
    }

    public function test_outbox_messages_carry_stable_event_id_for_inbox_dedup(): void
    {
        ($this->handler)(new PlaceOrderCommand(customerId: 'c', amount: 100));

        $messages = $this->outbox->findAll();
        $this->assertCount(1, $messages);
        $this->assertNotEmpty($messages[0]->id);
        $this->assertSame($messages[0]->id, $messages[0]->payload['eventId']);
    }

    public function test_pending_messages_are_returned_in_chronological_order(): void
    {
        ($this->handler)(new PlaceOrderCommand(customerId: 'a', amount: 100));
        usleep(1000);
        ($this->handler)(new PlaceOrderCommand(customerId: 'b', amount: 200));

        $pending = $this->outbox->findPending();
        $this->assertCount(2, $pending);
        $this->assertSame('a', $pending[0]->payload['customerId']);
        $this->assertSame('b', $pending[1]->payload['customerId']);
    }
}
