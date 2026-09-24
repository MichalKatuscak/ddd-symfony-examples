<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Handler;

use App\Chapter07_Sagas\Ordering\Application\Command\CancelOrderCommand;
use App\Chapter07_Sagas\Ordering\Application\Exception\AccessDeniedDomainException;
use App\Chapter07_Sagas\SharedKernel\Domain\SystemActor;
use App\Chapter11_OutboxPattern\Ordering\Domain\Repository\OrderRepository;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Asynchronní varianta z kapitoly o autorizaci. Pro ságu je nutná právě
 * tahle: musí rozpoznat systémovou identitu a uvolnit zámek, jinak
 * kompenzace narazí na OrderLockedBySagaException.
 */
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = $this->orders->get($command->orderId);

        // Systémová identita vlastníkem není a nikdy nebude, proto stojí ve
        // vlastní větvi. Bez ní handler odmítne vlastní kompenzaci ságy.
        $isSystem = $command->actorId->value === SystemActor::ID;

        if (!$isSystem && !$order->isOwnedBy($command->actorId)) {
            throw new AccessDeniedDomainException(
                sprintf('Cancel not allowed for order %s', $command->orderId->value),
            );
        }

        // Zámek patří procesu, takže si ho proces sám uvolní.
        if ($isSystem) {
            $order->releaseSagaLock();
        }

        $order->cancel(reason: $command->reason, when: new \DateTimeImmutable());
        $this->orders->save($order);

        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
