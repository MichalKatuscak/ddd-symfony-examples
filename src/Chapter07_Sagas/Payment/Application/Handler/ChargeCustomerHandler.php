<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Payment\Application\Handler;

use App\Chapter07_Sagas\Payment\Application\Command\ChargeCustomer;
use App\Chapter07_Sagas\Payment\Domain\Event\PaymentFailed;
use App\Chapter07_Sagas\Payment\Domain\Event\PaymentSucceeded;
use App\Chapter07_Sagas\Payment\Domain\PaymentGateway;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class ChargeCustomerHandler
{
    public function __construct(
        private PaymentGateway $gateway,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(ChargeCustomer $command): void
    {
        // Handler o sáze neví. Jen vykoná krok a oznámí výsledek;
        // co bude dál, rozhodne Process Manager.
        try {
            $transactionId = $this->gateway->charge(
                $command->customerId,
                $command->amountCents,
            );
        } catch (\RuntimeException $e) {
            // Selhání se hlásí událostí, ne výjimkou. Výjimka by skončila
            // v retry smyčce Messengeru a sága by se o neúspěchu nedozvěděla.
            $this->eventBus->dispatch(new PaymentFailed(
                eventId: Uuid::v7(),
                orderId: $command->orderId,
                failureReason: $e->getMessage(),
            ));

            return;
        }

        $this->eventBus->dispatch(new PaymentSucceeded(
            eventId: Uuid::v7(),
            orderId: $command->orderId,
            transactionId: $transactionId,
        ));
    }
}
