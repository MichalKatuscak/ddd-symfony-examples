<?php

declare(strict_types=1);

namespace App\Tests\Chapter07\Saga;

use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSaga;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaStatus;
use App\Chapter07_Sagas\Ordering\Infrastructure\Command\CheckStaleSagasCommand;
use App\Chapter07_Sagas\Ordering\Infrastructure\Saga\InMemoryOrderSagaRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/** Práh zaseklé ságy závisí na stavu, ne na jedné konstantě pro všechny. */
final class CheckStaleSagasCommandTest extends TestCase
{
    private InMemoryOrderSagaRepository $sagas;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->sagas = new InMemoryOrderSagaRepository();
        $this->tester = new CommandTester(new CheckStaleSagasCommand($this->sagas));
    }

    public function test_shipment_waiting_for_hours_is_not_stale(): void
    {
        // Dopravce smí potvrzovat až 24 hodin; 30minutový práh by tu hlásil falešný poplach.
        $this->saga('order-1', OrderSagaStatus::AwaitingShipment, idleFor: '-3 hours');

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
    }

    public function test_stock_reservation_idle_beyond_its_threshold_is_stale(): void
    {
        $this->saga('order-2', OrderSagaStatus::AwaitingStockReservation, idleFor: '-10 minutes');
        $this->saga('order-3', OrderSagaStatus::AwaitingPayment, idleFor: '-10 minutes');

        self::assertSame(Command::FAILURE, $this->tester->execute([]));
        $display = $this->tester->getDisplay();
        self::assertStringContainsString('order-2', $display);
        // Platba má práh 15 minut, po deseti ještě není zaseklá.
        self::assertStringNotContainsString('order-3', $display);
    }

    public function test_finished_saga_is_never_stale(): void
    {
        $saga = $this->saga('order-4', OrderSagaStatus::AwaitingPayment, idleFor: '-2 days');
        $saga->transitionTo(OrderSagaStatus::Completed);
        $this->backdate($saga, '-2 days');

        self::assertSame(Command::SUCCESS, $this->tester->execute([]));
    }

    private function saga(string $orderId, OrderSagaStatus $status, string $idleFor): OrderSaga
    {
        $saga = OrderSaga::start(sagaType: 'order_process', correlationId: $orderId, status: $status);
        $this->backdate($saga, $idleFor);
        $this->sagas->save($saga);

        return $saga;
    }

    /** Stav ságy nastavuje čas sám; test ho posune zpět, aby nemusel čekat. */
    private function backdate(OrderSaga $saga, string $modifier): void
    {
        (fn () => $this->updatedAt = new \DateTimeImmutable($modifier))->call($saga);
    }
}
