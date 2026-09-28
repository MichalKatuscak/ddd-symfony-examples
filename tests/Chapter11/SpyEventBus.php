<?php

declare(strict_types=1);

namespace App\Tests\Chapter11;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/** Sběrnice událostí, která zprávy jen zaznamená – test vidí, co handler odeslal. */
final class SpyEventBus implements MessageBusInterface
{
    /** @var list<object> */
    public array $messages = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->messages[] = $message;

        return new Envelope($message, $stamps);
    }
}
