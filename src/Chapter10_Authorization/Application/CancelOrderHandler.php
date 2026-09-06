<?php

declare(strict_types=1);

namespace App\Chapter10_Authorization\Application;

use App\Chapter02_AggregateDesign\Domain\Order\CustomerId;
use App\Chapter02_AggregateDesign\Domain\Order\Order;
use App\Chapter10_Authorization\Domain\SystemActor;

/**
 * Autorizace v asynchronním kontextu: ve workeru žádný token neexistuje,
 * takže se rozhoduje podle identity v příkazu.
 */
final readonly class CancelOrderHandler
{
    public function __invoke(Order $order, CustomerId $actorId, string $reason): void
    {
        // Systémová identita vlastníkem není a nikdy nebude, proto stojí
        // ve vlastní větvi. Bez ní handler odmítne vlastní kompenzaci ságy.
        $isSystem = $actorId->value === SystemActor::ID;

        if (!$isSystem && !$order->isOwnedBy($actorId)) {
            throw new AccessDeniedDomainException(sprintf(
                'Storno objednávky „%s“ tomuhle aktérovi nepřísluší.',
                $order->id->value,
            ));
        }

        // Zámek patří procesu, takže si ho proces sám uvolní.
        if ($isSystem) {
            $order->releaseSagaLock();
        }

        $order->cancel($reason, new \DateTimeImmutable());
    }
}
