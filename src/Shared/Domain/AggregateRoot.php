<?php

declare(strict_types=1);

namespace App\Shared\Domain;

abstract class AggregateRoot
{
    /** @var list<object> */
    private array $domainEvents = [];

    protected function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
