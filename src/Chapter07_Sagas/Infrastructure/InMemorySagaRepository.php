<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Infrastructure;

use App\Chapter07_Sagas\Domain\OrderFulfillmentSaga;

final class InMemorySagaRepository
{
    /** @var array<string, OrderFulfillmentSaga> */
    private array $sagas = [];

    public function save(OrderFulfillmentSaga $saga): void
    {
        $this->sagas[$saga->id()] = $saga;
    }

    public function findById(string $id): OrderFulfillmentSaga
    {
        if (!isset($this->sagas[$id])) {
            throw new \RuntimeException(sprintf('Saga "%s" not found.', $id));
        }

        return $this->sagas[$id];
    }
}
