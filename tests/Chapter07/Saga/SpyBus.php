<?php

declare(strict_types=1);

namespace App\Tests\Chapter07\Saga;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/** Sběrnice, která zprávy jen zaznamená – test vidí, co by sága odeslala. */
final class SpyBus implements MessageBusInterface
{
    /** @var list<object> */
    public array $messages = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->messages[] = $message;

        return new Envelope($message, $stamps);
    }
}
