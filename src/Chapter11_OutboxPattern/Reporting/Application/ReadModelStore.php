<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Reporting\Application;

interface ReadModelStore
{
    /**
     * Upsert – zpracování téže události podruhé nesmí nic pokazit.
     *
     * @param list<array{productId: string, quantity: int, unitPriceInCents: int}> $items
     */
    public function upsertOrderRow(
        string $orderId,
        string $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
    ): void;
}
