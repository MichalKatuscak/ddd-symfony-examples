<?php

declare(strict_types=1);

namespace App\Chapter11_OutboxPattern\Domain\Outbox;

interface OutboxRepositoryInterface
{
    public function save(OutboxMessage $message): void;

    /**
     * Načte nezpracované zprávy ve stejném pořadí, v jakém vznikly.
     *
     * @return OutboxMessage[]
     */
    public function findPending(int $limit = 100): array;

    /**
     * @return OutboxMessage[]
     */
    public function findAll(): array;
}
