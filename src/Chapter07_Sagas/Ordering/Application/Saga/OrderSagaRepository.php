<?php

declare(strict_types=1);

namespace App\Chapter07_Sagas\Ordering\Application\Saga;

/**
 * Rozhraní repozitáře stavu ságy – umožňuje záměnu
 * implementace (Doctrine v produkci, in-memory v testech i v ukázce).
 */
interface OrderSagaRepository
{
    public function save(OrderSaga $state): void;

    /**
     * Vrací null, když sága pro danou korelaci neexistuje. Objednávka mohla
     * vzniknout jinou cestou nebo ještě před nasazením procesu – a to není
     * chyba, kterou by měl hlásit repozitář.
     */
    public function findByCorrelationId(string $correlationId): ?OrderSaga;

    /** @return list<OrderSaga> */
    public function findStale(\DateTimeImmutable $olderThan): array;
}
