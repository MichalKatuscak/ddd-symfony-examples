<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Infrastructure;

/**
 * Velmi jednoduchý read-model store, který drží denormalizované záznamy
 * objednávek pro UI. Aktualizuje ho subscriber přes Idempotent Inbox.
 */
final class InMemoryReadModelStore
{
    /**
     * @var array<string, array{orderId: string, customerId: string, amount: int, placedAt: string, updatesApplied: int}>
     */
    private array $orders = [];

    public function recordOrderPlaced(
        string $orderId,
        string $customerId,
        int $amount,
        \DateTimeImmutable $placedAt,
    ): void {
        $existing = $this->orders[$orderId] ?? null;

        $this->orders[$orderId] = [
            'orderId' => $orderId,
            'customerId' => $customerId,
            'amount' => $amount,
            'placedAt' => $placedAt->format(\DateTimeInterface::ATOM),
            'updatesApplied' => ($existing['updatesApplied'] ?? 0) + 1,
        ];
    }

    /**
     * @return array<int, array{orderId: string, customerId: string, amount: int, placedAt: string, updatesApplied: int}>
     */
    public function findAll(): array
    {
        return array_values($this->orders);
    }

    public function findByOrderId(string $orderId): ?array
    {
        return $this->orders[$orderId] ?? null;
    }
}
