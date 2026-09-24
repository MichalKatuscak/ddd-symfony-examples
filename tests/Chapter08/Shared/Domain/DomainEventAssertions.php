<?php

declare(strict_types=1);

namespace App\Tests\Chapter08\Shared\Domain;

/**
 * Reusable trait pro ověřování doménových událostí v unit testech.
 * Použití: `use DomainEventAssertions;` ve třídě TestCase.
 *
 * Události se typují jako `object`, ne jako společná bázová třída.
 * Kanonický `AggregateRoot::record(object $event)` žádného předka
 * nevyžaduje.
 */
trait DomainEventAssertions
{
    /**
     * Ověří, že kolekce událostí obsahuje právě jednu událost daného typu.
     *
     * @param array<object> $events
     */
    protected function assertSingleEventOfType(string $expectedType, array $events): object
    {
        $matching = array_filter($events, fn(object $e) => $e instanceof $expectedType);

        $this->assertCount(
            1,
            $matching,
            sprintf('Očekávána právě jedna událost typu %s, nalezeno %d.', $expectedType, count($matching))
        );

        return array_values($matching)[0];
    }

    /**
     * Ověří, že kolekce událostí neobsahuje žádnou událost daného typu.
     *
     * @param array<object> $events
     */
    protected function assertNoEventOfType(string $unexpectedType, array $events): void
    {
        $matching = array_filter($events, fn(object $e) => $e instanceof $unexpectedType);

        $this->assertCount(
            0,
            $matching,
            sprintf('Neočekávána žádná událost typu %s, ale nalezena.', $unexpectedType)
        );
    }

    /**
     * Ověří přesné pořadí vydaných událostí.
     *
     * @param array<class-string> $expectedTypes
     * @param array<object>       $events
     */
    protected function assertEventSequence(array $expectedTypes, array $events): void
    {
        $actualTypes = array_map(fn(object $e) => $e::class, $events);

        $this->assertSame(
            $expectedTypes,
            $actualTypes,
            'Pořadí doménových událostí neodpovídá očekávání.'
        );
    }
}
