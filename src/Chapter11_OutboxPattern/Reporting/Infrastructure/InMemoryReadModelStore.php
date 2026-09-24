<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Reporting\Infrastructure;

use App\Chapter11_OutboxPattern\Reporting\Application\ReadModelStore;

/**
 * Náhrada DbalReadModelStore z knihy (INSERT … ON CONFLICT DO UPDATE nad
 * tabulkou reporting_orders). Upsert zůstává upsertem.
 *
 * Sloupec `writes` v knize není. Ukázka jím počítá, kolikrát subscriber
 * na řádek sáhl, aby bylo vidět, že duplicitní doručení zastavil inbox.
 */
final class InMemoryReadModelStore implements ReadModelStore
{
    /**
     * @var array<string, array{orderId: string, customerId: string, items: list<array{productId: string, quantity: int, unitPriceInCents: int}>, placedAt: \DateTimeImmutable, writes: int}>
     */
    private array $rows = [];

    public function upsertOrderRow(
        string $orderId,
        string $customerId,
        array $items,
        \DateTimeImmutable $placedAt,
    ): void {
        $this->rows[$orderId] = [
            'orderId' => $orderId,
            'customerId' => $customerId,
            'items' => $items,
            'placedAt' => $this->rows[$orderId]['placedAt'] ?? $placedAt,
            'writes' => ($this->rows[$orderId]['writes'] ?? 0) + 1,
        ];
    }

    /**
     * @return array{orderId: string, customerId: string, items: list<array{productId: string, quantity: int, unitPriceInCents: int}>, placedAt: \DateTimeImmutable, writes: int}|null
     */
    public function find(string $orderId): ?array
    {
        return $this->rows[$orderId] ?? null;
    }

    /**
     * @return list<array{orderId: string, customerId: string, items: list<array{productId: string, quantity: int, unitPriceInCents: int}>, placedAt: \DateTimeImmutable, writes: int}>
     */
    public function all(): array
    {
        return array_values($this->rows);
    }
}
