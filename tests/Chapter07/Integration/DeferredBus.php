<?php

declare(strict_types=1);

namespace App\Tests\Chapter07\Integration;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/** Sběrnice, jejíž skutečnou implementaci test dosadí až po sestavení handlerů. */
final class DeferredBus implements MessageBusInterface
{
    public ?MessageBusInterface $inner = null;

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        return ($this->inner ?? throw new \LogicException('Sběrnice není sestavená.'))->dispatch($message, $stamps);
    }
}
