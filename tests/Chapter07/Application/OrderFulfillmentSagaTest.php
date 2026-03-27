<?php

declare(strict_types=1);
namespace App\Tests\Chapter07\Application;
use App\Chapter07_Sagas\Application\OrderFulfillmentSaga;
use PHPUnit\Framework\TestCase;

final class OrderFulfillmentSagaTest extends TestCase
{
    public function test_successful_fulfillment(): void
    {
        $saga = new OrderFulfillmentSaga();
        $log = $saga->execute('order-1', stockAvailable: true, paymentSuccess: true);
        $this->assertContains('ReserveStock: OK', $log);
        $this->assertContains('ProcessPayment: OK', $log);
        $this->assertContains('ShipOrder: OK', $log);
    }

    public function test_payment_failure_triggers_compensation(): void
    {
        $saga = new OrderFulfillmentSaga();
        $log = $saga->execute('order-1', stockAvailable: true, paymentSuccess: false);
        $this->assertContains('ProcessPayment: FAILED', $log);
        $this->assertContains('ReserveStock: COMPENSATED (stock released)', $log);
        $this->assertNotContains('ShipOrder: OK', $log);
    }
}
