<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventSourcing;

use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;

/**
 * Výchozí implementace. Correlation ID drží celý request, causation ID
 * ukazuje na událost, která tuhle vyvolala – bez nich se řetěz příčin
 * v Event Store zpětně nedá poskládat.
 */
final class RequestEventMetadataProvider implements EventMetadataProvider
{
    private ?string $correlationId = null;
    private ?string $causationId = null;
    private ?string $userId = null;

    public function bind(?string $correlationId, ?string $causationId, ?string $userId): void
    {
        $this->correlationId = $correlationId;
        $this->causationId   = $causationId;
        $this->userId        = $userId;
    }

    /** @return array<string, mixed> */
    public function forEvent(DomainEvent $event): array
    {
        return [
            'correlationId' => $this->correlationId ?? $event->eventId,
            'causationId'   => $this->causationId,
            'userId'        => $this->userId,
        ];
    }
}
