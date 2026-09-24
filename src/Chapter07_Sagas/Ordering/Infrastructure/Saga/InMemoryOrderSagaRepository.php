<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Infrastructure\Saga;

use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSaga;
use App\Chapter07_Sagas\Ordering\Application\Saga\OrderSagaRepository;
use Doctrine\DBAL\Driver\AbstractException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * Stav ság v paměti. Celý proces v ukázce doběhne synchronně v jednom
 * requestu, takže není co obnovovat po restartu workeru.
 *
 * Unikátní index (saga_type, correlation_id) z knihy repozitář napodobuje:
 * druhá sága pro tutéž korelaci skončí stejnou výjimkou, jakou by vyhodila
 * databáze. Process Manager na ni reaguje jako v knize.
 */
final class InMemoryOrderSagaRepository implements OrderSagaRepository
{
    /** @var array<string, OrderSaga> */
    private array $states = [];

    public function save(OrderSaga $state): void
    {
        $key = $state->sagaType() . '::' . $state->correlationId();
        $existing = $this->states[$key] ?? null;

        if ($existing !== null && $existing !== $state) {
            throw new UniqueConstraintViolationException(
                new class('UNIQUE constraint failed: order_saga.saga_type, order_saga.correlation_id') extends AbstractException {},
                null,
            );
        }

        $this->states[$key] = $state;
    }

    public function findByCorrelationId(string $correlationId): ?OrderSaga
    {
        foreach ($this->states as $state) {
            if ($state->correlationId() === $correlationId) {
                return $state;
            }
        }

        return null;
    }

    /** @return list<OrderSaga> */
    public function findStale(\DateTimeImmutable $olderThan): array
    {
        return array_values(array_filter(
            $this->states,
            static fn (OrderSaga $s): bool => !$s->isTerminated() && $s->updatedAt() < $olderThan,
        ));
    }
}
