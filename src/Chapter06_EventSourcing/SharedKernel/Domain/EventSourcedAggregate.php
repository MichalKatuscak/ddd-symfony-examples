<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\SharedKernel\Domain;

use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;

abstract class EventSourcedAggregate
{
    /** @var list<DomainEvent> Události nahrané v aktuální transakci – čekají na uložení. */
    private array $recordedEvents = [];

    private int $version = 0;

    /**
     * Nová událost: aplikuje se na stav, zapamatuje pro persistenci
     * a zvýší verzi streamu – nezbytné pro optimistic locking.
     *
     * Jméno se záměrně liší od record() ve stavově ukládaném AggregateRoot:
     * zde metoda událost navíc aplikuje a inkrementuje verzi.
     */
    protected function recordEvent(DomainEvent $event): void
    {
        $this->applyEvent($event);
        $this->recordedEvents[] = $event;
        $this->version++;
    }

    /**
     * Přehraje historické události z Event Store (bez přidávání do $recordedEvents).
     *
     * @param list<DomainEvent> $events
     */
    public static function reconstituteFromEvents(array $events): static
    {
        $aggregate = new static();

        foreach ($events as $event) {
            $aggregate->applyEvent($event);
            $aggregate->version++;
        }

        return $aggregate;
    }

    /**
     * Dynamické dispatchování na apply*() metody podle třídy události.
     * Konvence: apply + ShortClassName, např. applyOrderPlaced().
     * apply*() metody v podtřídách MUSÍ být protected (ne private),
     * jinak je PHP nemůže volat z kontextu této nadtřídy.
     */
    private function applyEvent(DomainEvent $event): void
    {
        $method = 'apply' . (new \ReflectionClass($event))->getShortName();

        if (!method_exists($this, $method)) {
            throw new \LogicException(
                sprintf('Aggregate %s must implement %s().', static::class, $method),
            );
        }

        $this->$method($event);
    }

    /**
     * Nahrané události bez jejich vyjmutí. Repozitář je potřebuje vidět
     * ještě před zápisem – kdyby je vyjmul předem a append() selhal
     * na konfliktu verzí, byly by z agregátu nenávratně pryč.
     *
     * @return list<DomainEvent>
     */
    public function recordedEvents(): array
    {
        return $this->recordedEvents;
    }

    /** @return list<DomainEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    public function version(): int
    {
        return $this->version;
    }
}
