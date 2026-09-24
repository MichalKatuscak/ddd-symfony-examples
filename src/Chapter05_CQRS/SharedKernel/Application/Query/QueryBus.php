<?php

declare(strict_types=1);

namespace App\Chapter05_CQRS\SharedKernel\Application\Query;

use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Bezpečnější odběr výsledku dotazu (kniha 12.10). HandleTrait vrátí
 * výsledek handleru a ověří, že zprávu obsloužil právě jeden – chybějící
 * handler skončí srozumitelnou LogicException, ne voláním metody nad null.
 *
 * Kniha cílí na 'query.bus', sdílená konfigurace ukázek má messenger.bus.query.
 */
final class QueryBus
{
    use HandleTrait;

    public function __construct(
        #[Target('messenger.bus.query')]
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function ask(object $query): mixed
    {
        return $this->handle($query);
    }
}
