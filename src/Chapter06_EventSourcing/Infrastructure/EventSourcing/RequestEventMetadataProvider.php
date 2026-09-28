<?php

declare(strict_types=1);

namespace App\Chapter06_EventSourcing\Infrastructure\EventSourcing;

use App\Chapter06_EventSourcing\SharedKernel\Domain\Event\DomainEvent;

/**
 * Výchozí implementace. Correlation ID drží celý request nebo spouštěcí
 * zprávu a přechází i do zpráv z nich odvozených. Causation ID ukazuje
 * na bezprostřední příčinu, tedy příkaz nebo událost, která tuto vyvolala.
 * Bez nich se řetěz příčin v Event Store zpětně nedá poskládat.
 *
 * bind() volá request listener nebo middleware Messengeru při převzetí
 * zprávy; ID přebírá z hlavičky, případně ze stampu spouštěcí zprávy.
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
            // Žádný fallback na $event->eventId: každá událost by dostala
            // vlastní korelaci a řetěz by se rozpadl. Bez navázaného
            // kontextu (konzolový příkaz, test) zůstává null.
            'correlationId' => $this->correlationId,
            'causationId'   => $this->causationId,
            'userId'        => $this->userId,
        ];
    }
}
