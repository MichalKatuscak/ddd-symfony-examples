<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventSourcing;

use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;

/**
 * Metadata k události: kdo ji vyvolal a v jaké souvislosti. Doménový model
 * o requestu nic neví, proto je dodává infrastruktura až při zápisu.
 */
interface EventMetadataProvider
{
    /** @return array<string, mixed> */
    public function forEvent(DomainEvent $event): array;
}
