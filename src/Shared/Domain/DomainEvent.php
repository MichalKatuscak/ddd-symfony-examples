<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Pomocné rozhraní ukázek, kniha ho nemá. Kanonické události z kapitol
 * Základní koncepty a Návrh agregátu jsou final readonly třídy bez předka
 * s veřejnou vlastností $occurredAt (tak je píší i ukázky kapitol 6 a 7).
 * Rozhraní používají ukázky, jejichž event store, projekce nebo outbox
 * potřebují čas události přečíst bez znalosti konkrétní třídy.
 */
interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
