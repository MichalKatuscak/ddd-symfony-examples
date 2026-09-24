<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Payment\Application\Handler;

use App\Chapter07_Sagas\Payment\Application\Command\RefundCustomer;
use App\Chapter07_Sagas\Payment\Domain\Event\RefundFailed;
use App\Chapter07_Sagas\Payment\Domain\Event\RefundSucceeded;
use App\Chapter07_Sagas\Payment\Domain\PaymentGateway;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Protějšek ChargeCustomerHandler: zavolá PaymentGateway::refund() a podle
 * výsledku vydá RefundSucceeded, nebo RefundFailed.
 */
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class RefundCustomerHandler
{
    public function __construct(
        private PaymentGateway $gateway,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(RefundCustomer $command): void
    {
        try {
            $refundId = $this->gateway->refund($command->transactionId, $command->amountCents);
        } catch (\RuntimeException $e) {
            $this->eventBus->dispatch(new RefundFailed(
                eventId: Uuid::v7(),
                orderId: $command->orderId,
                failureReason: $e->getMessage(),
            ));

            return;
        }

        $this->eventBus->dispatch(new RefundSucceeded(
            eventId: Uuid::v7(),
            orderId: $command->orderId,
            refundId: $refundId,
        ));
    }
}
