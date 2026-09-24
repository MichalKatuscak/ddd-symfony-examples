<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Bázová třída kořene agregátu podle knihy (Základní koncepty, 06.09).
 *
 * Agregát událost zaznamená v doménové metodě, aplikační vrstva ji
 * vyzvedne až po uložení. Obě metody jsou final: podtřída nemá jak
 * frontu obejít nebo vyprázdnit jinak než přes releaseEvents().
 */
abstract class AggregateRoot
{
    /** @var list<object> */
    private array $domainEvents = [];

    final protected function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    final public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
