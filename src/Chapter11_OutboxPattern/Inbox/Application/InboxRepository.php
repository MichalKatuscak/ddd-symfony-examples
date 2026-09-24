<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Inbox\Application;

use Symfony\Component\Uid\Uuid;

interface InboxRepository
{
    public function isProcessed(Uuid $eventId, string $consumer): bool;

    /**
     * Zapíše dvojici (eventId, consumer). Duplicitu odmítne výjimkou –
     * UNIQUE constraint je pojistka proti souběhu, ne chyba k tichému
     * spolknutí.
     */
    public function markProcessed(Uuid $eventId, string $consumer): void;
}
