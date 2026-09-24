<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Application\Handler;

use App\Chapter10_Authorization\Application\Command\CancelOrderCommand;
use App\Chapter10_Authorization\Application\Exception\AccessDeniedDomainException;
use App\Chapter10_Authorization\Domain\Repository\OrderRepository;
use App\Chapter10_Authorization\Domain\SystemActor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Asynchronní varianta handleru z kapitoly (11.05) – ta, která jde do
 * projektu. Ve workeru žádný token neexistuje, takže se rozhoduje podle
 * identity v příkazu.
 *
 * V knize nese #[AsMessageHandler(bus: 'command.bus')]. Ukázka atribut
 * vynechává: command bus se v tomto projektu jmenuje messenger.bus.command
 * a repozitář objednávek má jen in-memory podobu v testech, takže handler
 * volá jen test.
 */
final readonly class CancelOrderHandler
{
    public function __construct(
        private OrderRepository $orders,
        private EntityManagerInterface $em,
        #[Target('event.bus')]
        private MessageBusInterface $eventBus,
    ) {}

    public function __invoke(CancelOrderCommand $command): void
    {
        $order = $this->orders->get($command->orderId);

        // Autorizace proti identitě v commandu – token ve workeru neexistuje.
        // Systémová identita vlastníkem není a nikdy nebude, proto stojí ve
        // vlastní větvi. Bez ní handler odmítne vlastní kompenzaci ságy.
        $isSystem = $command->actorId->value === SystemActor::ID;

        if (!$isSystem && !$order->isOwnedBy($command->actorId)) {
            throw new AccessDeniedDomainException(
                sprintf('Cancel not allowed for order %s', $command->orderId->value)
            );
        }

        // Zámek patří procesu, takže si ho proces sám uvolní. Kdyby to
        // dělal až samostatný příkaz, záleželo by na pořadí ve frontě.
        if ($isSystem) {
            $order->releaseSagaLock();
        }

        $order->cancel(reason: $command->reason, when: new \DateTimeImmutable());
        $this->orders->save($order);
        $this->em->flush();

        // Bez tohohle kroku agregát skončí v cancelled, ale read model
        // zůstane na původním stavu. Nic nespadne – stavy se jen rozejdou.
        foreach ($order->releaseEvents() as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
